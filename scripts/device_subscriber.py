#!/usr/bin/env python3
"""
边缘设备命令监听程序（守护进程）- 树莓派版本
- 持续运行，7x24小时在线
- 订阅命令Topic和配置Topic
- 执行设备开关操作
- 保存配置到JSON文件
- 回复ACK

树莓派部署步骤:
    1. 安装依赖: pip3 install paho-mqtt
    2. 复制到树莓派: scp device_subscriber.py pi@raspberrypi:/home/pi/
    3. 后台运行: nohup python3 /home/pi/device_subscriber.py CH003 192.168.1.100 >> /var/log/mqtt.log 2>&1 &

用法:
    python3 device_subscriber.py CH003 <broker_ip> [config_dir]
    
参数:
    CH003       - 设备编码 (必填)
    broker_ip   - MQTT Broker IP (必填, 例如: 192.168.1.100)
    config_dir  - 配置文件目录 (可选, 默认: ./config)
"""

import sys
import json
import time
import signal
import threading
import tempfile
import os
import logging
from datetime import datetime
from pathlib import Path

# paho-mqtt v2 兼容处理
try:
    import paho.mqtt.client as mqtt
    # 检测版本
    if hasattr(mqtt, 'CallbackAPIVersion'):
        # v2 版本
        PAHO_V2 = True
    else:
        # v1 版本
        PAHO_V2 = False
except ImportError:
    print("✗ 错误: 请先安装 paho-mqtt")
    print("   pip3 install paho-mqtt")
    sys.exit(1)

# ========== 配置解析 ==========
if len(sys.argv) < 3:
    print("错误: 参数不足")
    print("用法: python3 device_subscriber.py <设备编码> <broker_ip> [config_dir]")
    print("示例: python3 device_subscriber.py CH003 192.168.1.100")
    print("示例: python3 device_subscriber.py CH003 192.168.1.100 /home/pi/config")
    sys.exit(1)

device_code = sys.argv[1]
broker = sys.argv[2]
port = 1883
username = 'device'
password = 'device_password'
client_id = f"subscriber_{device_code}_{int(time.time())}"

# 配置文件目录
config_dir = sys.argv[3] if len(sys.argv) > 3 else './config'
config_path = Path(config_dir)
config_path.mkdir(parents=True, exist_ok=True)

# 日志配置
log_dir = Path('/var/log') if os.access('/var/log', os.W_OK) else config_path
log_file = log_dir / f"device_{device_code}.log"

# 设置日志
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(log_file, encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# 状态文件路径
states_file = config_path / 'device_states.json'

# ========== 设备状态 ==========
device_states = {
    'inner_circulation': False,
    'cooling': False,
    'heating': False,
    'fan': False,
    'four_way_valve': False,
    'fresh_air': False,
    'humidification': False,
    'lighting_supplement': False,
    'lighting': False,
}

running = True
state_lock = threading.Lock()
client_instance = None  # 全局客户端引用


# ========== 状态持久化 ==========
def load_device_states():
    """启动时从文件加载设备状态"""
    global device_states
    try:
        if states_file.exists():
            with open(states_file, 'r', encoding='utf-8') as f:
                data = json.load(f)
                loaded_states = data.get('states', {})
                with state_lock:
                    device_states.update(loaded_states)
                logger.info(f"✓ 已加载设备状态: {json.dumps(loaded_states)}")
        else:
            logger.info("⚠ 未找到设备状态文件，使用默认状态")
            save_device_states()  # 创建初始状态文件
    except json.JSONDecodeError as e:
        logger.error(f"✗ 设备状态文件 JSON 格式错误: {e}")
    except Exception as e:
        logger.error(f"✗ 加载设备状态失败: {e}")


def save_device_states():
    """保存设备状态到JSON文件（原子写入）"""
    try:
        with state_lock:
            data = {
                'device_code': device_code,
                'updated_at': datetime.now().isoformat(),
                'states': device_states.copy(),
            }
        
        # 原子写入
        tmp_file = states_file.with_suffix('.tmp')
        with open(tmp_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            f.flush()
            os.fsync(f.fileno())
        os.replace(tmp_file, states_file)
        logger.debug("设备状态已保存")
    except Exception as e:
        logger.error(f"✗ 保存设备状态失败: {e}")


def save_config_file(config_type, config_data, config_id):
    """保存配置到JSON文件"""
    try:
        config_file = config_path / f"{config_type}.json"
        data = {
            'config_id': config_id,
            'device_code': device_code,
            'received_at': datetime.now().isoformat(),
            'control_type': config_type,
            'config': config_data,
        }
        
        tmp_file = config_file.with_suffix('.tmp')
        with open(tmp_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            f.flush()
            os.fsync(f.fileno())
        os.replace(tmp_file, config_file)
        return True
    except Exception as e:
        logger.error(f"✗ 保存配置失败: {e}")
        return False


# ========== 信号处理 ==========
def signal_handler(sig, frame):
    global running
    logger.info("收到停止信号，正在退出...")
    running = False


signal.signal(signal.SIGINT, signal_handler)
signal.signal(signal.SIGTERM, signal_handler)


# ========== MQTT 回调 ==========
def on_connect(client, userdata, flags, rc_or_reason, properties=None):
    """连接成功回调"""
    if PAHO_V2:
        # v2 版本: rc_or_reason 是 ReasonCodes 对象
        if rc_or_reason.is_failure:
            logger.error(f"✗ 连接失败: {rc_or_reason}")
            return
        logger.info("✓ 连接成功！")
    else:
        # v1 版本: rc_or_reason 是整数返回码
        if rc_or_reason != 0:
            logger.error(f"✗ 连接失败，返回码: {rc_or_reason}")
            return
        logger.info("✓ 连接成功！")
    
    # 订阅命令和配置Topic
    client.subscribe(f"chambers/{device_code}/command/manual", qos=1)
    client.subscribe(f"chambers/{device_code}/config/auto", qos=1)
    logger.info(f"✓ 已订阅命令 Topic: chambers/{device_code}/command/manual")
    logger.info(f"✓ 已订阅配置 Topic: chambers/{device_code}/config/auto")
    
    # 发送设备上线状态
    status_msg = {
        'online': True,
        'timestamp': datetime.now().isoformat(),
        'device_code': device_code,
    }
    client.publish(f"chambers/{device_code}/status", json.dumps(status_msg), qos=1)
    logger.info("✓ 设备上线状态已上报")


def on_disconnect(client, userdata, rc_or_disconnect_flags, rc=None, properties=None):
    """断开连接回调"""
    if PAHO_V2:
        # v2 版本
        if rc is not None and rc != 0:
            logger.warning(f"⚠ 异常断开，返回码: {rc}，将自动重连...")
        else:
            logger.info("✓ 正常断开")
    else:
        # v1 版本
        if rc_or_disconnect_flags != 0:
            logger.warning(f"⚠ 异常断开，返回码: {rc_or_disconnect_flags}，将自动重连...")
        else:
            logger.info("✓ 正常断开")


def on_message(client, userdata, msg):
    """收到消息回调"""
    topic = msg.topic
    
    # JSON 解析
    try:
        payload = json.loads(msg.payload.decode())
    except json.JSONDecodeError as e:
        logger.error(f"✗ 收到非法 JSON payload: {e}")
        logger.debug(f"  Topic: {topic}, Payload: {msg.payload[:200]}")
        return
    except Exception as e:
        logger.error(f"✗ 解析消息失败: {e}")
        return
    
    try:
        if 'command' in topic:
            handle_command(client, payload)
        elif 'config' in topic:
            handle_config(client, payload)
    except Exception as e:
        logger.error(f"✗ 处理消息时发生错误: {e}")
        logger.debug(f"  Topic: {topic}")


def handle_command(client, payload):
    """处理手动控制命令"""
    logger.info(f"【收到命令】{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    logger.info(f"Command ID: {payload.get('command_id')}")
    logger.info(f"Actions: {json.dumps(payload.get('actions', {}))}")
    
    # 执行命令
    actions = payload.get('actions', {})
    executed_actions = {}
    with state_lock:
        for device, state in actions.items():
            if device in device_states:
                device_states[device] = bool(state)
                executed_actions[device] = bool(state)
                logger.info(f"  → {device}: {'开启' if state else '关闭'}")
    
    # 保存设备状态
    save_device_states()
    
    # 模拟执行耗时
    time.sleep(1)
    
    # 发送ACK
    ack = {
        'command_id': payload.get('command_id'),
        'status': 'success',
        'executed_at': int(time.time()),
        'executed_actions': executed_actions,
    }
    client.publish(f"chambers/{device_code}/ack", json.dumps(ack), qos=1)
    logger.info("✓ ACK 已发送")


def handle_config(client, payload):
    """处理自动控制配置"""
    logger.info(f"【收到配置】{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    logger.info(f"Config ID: {payload.get('config_id')}")
    logger.info(f"Control Type: {payload.get('control_type')}")
    
    config_type = payload.get('control_type')
    config_data = payload.get('config', {})
    config_id = payload.get('config_id')
    
    # 保存配置
    if config_type:
        save_success = save_config_file(config_type, config_data, config_id)
        if save_success:
            logger.info(f"✓ 配置已保存: {config_type}.json")
        else:
            logger.error(f"✗ 保存配置失败: {config_type}")
    else:
        logger.warning("⚠ 警告: 未找到 control_type")
        save_success = False
    
    # 发送ACK
    ack = {
        'command_id': config_id,
        'status': 'success' if save_success else 'failed',
        'received_at': int(time.time()),
    }
    if not save_success:
        ack['error'] = '保存失败'
    
    client.publish(f"chambers/{device_code}/ack", json.dumps(ack), qos=1)
    logger.info("✓ ACK 已发送")


# ========== 主程序 ==========
def create_client():
    """创建MQTT客户端（兼容v1/v2）"""
    if PAHO_V2:
        # paho-mqtt v2
        client = mqtt.Client(
            callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
            client_id=client_id,
            clean_session=False
        )
    else:
        # paho-mqtt v1
        client = mqtt.Client(client_id=client_id, clean_session=False)
    
    client.username_pw_set(username, password)
    client.on_connect = on_connect
    client.on_message = on_message
    client.on_disconnect = on_disconnect
    
    # 启用自动重连
    client.reconnect_delay_set(min_delay=1, max_delay=30)
    
    return client


def main():
    global running, client_instance
    
    logger.info("=" * 60)
    logger.info(f"设备 {device_code} 命令监听程序启动")
    logger.info(f"Broker: {broker}:{port}")
    logger.info(f"配置文件目录: {config_path}")
    logger.info(f"日志文件: {log_file}")
    logger.info("=" * 60)
    
    # 加载设备状态
    load_device_states()
    
    # 创建客户端
    client = create_client()
    client_instance = client
    
    reconnect_count = 0
    max_reconnect = 100  # 最大重连次数
    
    while running and reconnect_count < max_reconnect:
        try:
            logger.info(f"正在连接 MQTT Broker ({broker}:{port})...")
            client.connect(broker, port, keepalive=60)
            client.loop_start()
            
            reconnect_count = 0  # 重置重连计数
            logger.info("监听中...")
            
            # 主循环
            while running:
                time.sleep(1)
                
                # 检查连接状态
                if not client.is_connected():
                    logger.warning("检测到连接断开，准备重连...")
                    break
            
            # 正常退出或需要重连
            client.loop_stop()
            client.disconnect()
            
        except Exception as e:
            reconnect_count += 1
            logger.error(f"✗ 连接错误: {e}")
            logger.info(f"将在 5 秒后重连... (第 {reconnect_count}/{max_reconnect} 次)")
            time.sleep(5)
    
    if reconnect_count >= max_reconnect:
        logger.error("✗ 达到最大重连次数，程序退出")
    else:
        logger.info("✓ 程序正常退出")


if __name__ == '__main__':
    main()
