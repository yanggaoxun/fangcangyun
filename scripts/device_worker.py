#!/usr/bin/env python3
"""
设备工作主程序（长连接守护进程）
- 整合传感器读取、MQTT通信、设备控制
- 同时支持数据上报和命令接收

树莓派部署:
    1. 安装依赖: pip3 install paho-mqtt pyserial
    2. 复制文件到树莓派
    3. 修改 config.py 中的配置
    4. 后台运行: nohup python3 device_worker.py >> /var/log/device_worker.log 2>&1 &

Supervisor 配置:
    [program:device-worker]
    command=python3 /home/pi/device_worker.py
    directory=/home/pi
    autostart=true
    autorestart=true
    startretries=10
    stderr_logfile=/var/log/device_worker.err.log
    stdout_logfile=/var/log/device_worker.out.log
"""

import os
import sys
import time
import signal
import logging
from datetime import datetime

# 确保可以找到同级模块
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import config
from mqtt_handler import MqttHandler
from sensor_reader import read_all_data, generate_mock_data
from device_controller import DeviceController


# ========== 全局变量 ==========
running = True
controller = None
mqtt_handler = None


# ========== 日志配置 ==========
def setup_logging():
    """配置日志系统"""
    # 确保日志目录存在
    config.LOG_DIR.mkdir(parents=True, exist_ok=True)
    config.CONFIG_DIR.mkdir(parents=True, exist_ok=True)
    
    # 设置日志级别
    level = getattr(logging, config.LOG_LEVEL.upper(), logging.INFO)
    
    logging.basicConfig(
        level=level,
        format=config.LOG_FORMAT,
        handlers=[
            logging.FileHandler(config.LOG_FILE, encoding='utf-8'),
            logging.StreamHandler(sys.stdout)
        ]
    )
    
    return logging.getLogger(__name__)


# ========== 信号处理 ==========
def signal_handler(sig, frame):
    """处理停止信号"""
    global running
    logger.info("收到停止信号，正在退出...")
    running = False


signal.signal(signal.SIGINT, signal_handler)
signal.signal(signal.SIGTERM, signal_handler)


# ========== 命令回调 ==========
def on_command(actions):
    """
    收到手动控制命令时的回调
    
    Args:
        actions: dict, 例如 {'cooling': True, 'heating': False}
    
    Returns:
        dict: 实际执行的动作
    """
    logger.info(f"执行命令: {actions}")
    executed = controller.execute_actions(actions)
    
    # 模拟执行耗时
    time.sleep(1)
    
    return executed


def on_config(config_type, config_data, config_id):
    """
    收到自动配置命令时的回调
    
    Args:
        config_type: 配置类型
        config_data: 配置数据
        config_id: 配置ID
    
    Returns:
        bool: 是否保存成功
    """
    logger.info(f"保存配置: {config_type}")
    return controller.save_config(config_type, config_data, config_id)


# ========== 主程序 ==========
def main():
    global running, controller, mqtt_handler, logger
    
    # 初始化日志
    logger = setup_logging()
    
    logger.info("=" * 60)
    logger.info(f"设备 {config.DEVICE_CODE} 工作程序启动")
    logger.info(f"MQTT Broker: {config.MQTT_BROKER}:{config.MQTT_PORT}")
    logger.info(f"上报间隔: {config.REPORT_INTERVAL} 秒")
    logger.info(f"状态上报间隔: {config.STATUS_REPORT_INTERVAL} 秒")
    logger.info(f"GPIO模式: {config.GPIO_MODE}")
    logger.info(f"继电器类型: {'NO' if config.GPIO_RELAY_NO else 'NC'}")
    logger.info(f"上电初始状态: {'恢复上次' if config.GPIO_INITIAL_STATE == 'restore' else '全部关闭'}")
    logger.info(f"日志文件: {config.LOG_FILE}")
    logger.info("=" * 60)
    
    # 初始化设备控制器
    controller = DeviceController()
    
    # 初始化 MQTT 处理器
    mqtt_handler = MqttHandler(
        device_code=config.DEVICE_CODE,
        on_command=on_command,
        on_config=on_config
    )
    
    # 连接 MQTT
    if not mqtt_handler.connect():
        logger.error("✗ MQTT 连接失败，程序退出")
        sys.exit(1)
    
    # 等待连接建立
    time.sleep(2)
    
    # 初始化时间记录
    last_report_time = 0
    last_status_time = 0
    reconnect_count = 0
    
    logger.info("开始主循环...")
    
    try:
        while running and reconnect_count < config.MAX_RECONNECT_COUNT:
            current_time = time.time()
            
            # 检查 MQTT 连接状态
            if not mqtt_handler.is_connected():
                reconnect_count += 1
                logger.warning(f"⚠ MQTT 连接断开，准备重连... (第 {reconnect_count} 次)")
                
                # 尝试重连
                mqtt_handler.disconnect()
                time.sleep(5)
                
                if mqtt_handler.connect():
                    reconnect_count = 0
                    logger.info("✓ MQTT 重连成功")
                    time.sleep(2)
                else:
                    logger.error(f"✗ MQTT 重连失败，{config.RECONNECT_MIN_DELAY}秒后重试")
                    time.sleep(config.RECONNECT_MIN_DELAY)
                
                continue
            
            # 1. 上报传感器数据
            if current_time - last_report_time >= config.REPORT_INTERVAL:
                logger.info(f"【上报数据】{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
                
                # 读取传感器数据
                try:
                    if config.SERIAL_PORT and os.path.exists(config.SERIAL_PORT):
                        # 有串口设备，读取真实数据
                        data = read_all_data()
                        logger.info(f"  温度: {data.get('temperature')}°C, "
                                  f"湿度: {data.get('humidity')}%, "
                                  f"CO2: {data.get('co2_level')} ppm")
                    else:
                        # 无串口设备，使用模拟数据（测试用）
                        logger.warning("未检测到串口设备，使用模拟数据")
                        data = generate_mock_data()
                    
                    # 添加设备开关状态到上报数据
                    data['devices'] = controller.get_states()
                    
                except Exception as e:
                    logger.error(f"读取传感器失败: {e}")
                    data = generate_mock_data()
                    data['devices'] = controller.get_states()
                
                # 上报到 MQTT
                if mqtt_handler.publish_data(data):
                    last_report_time = current_time
                else:
                    logger.error("数据上报失败")
            
            # 2. 上报设备在线状态
            if current_time - last_status_time >= config.STATUS_REPORT_INTERVAL:
                mqtt_handler.publish_status(True)
                last_status_time = current_time
            
            # 3. 主循环休眠（降低CPU占用）
            time.sleep(0.5)
        
        # 正常退出
        logger.info("正在清理...")
        mqtt_handler.disconnect()
        controller.cleanup()
        logger.info("✓ 程序正常退出")
        
    except Exception as e:
        logger.error(f"✗ 主循环异常: {e}", exc_info=True)
        if mqtt_handler:
            mqtt_handler.disconnect()
        if controller:
            controller.cleanup()
        sys.exit(1)


if __name__ == '__main__':
    main()
