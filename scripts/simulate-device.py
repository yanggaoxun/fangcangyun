#!/usr/bin/env python3
"""
边缘设备模拟器

用法: 
    python3 simulate-device.py CH001              # 默认连接 emqx（Docker 内部）
    python3 simulate-device.py CH001 localhost    # 连接本地 MQTT Broker
    python3 simulate-device.py CH001 192.168.1.100 # 连接指定 IP
"""

import sys
import json
import time
import random
import signal
from datetime import datetime
import paho.mqtt.client as mqtt

# 配置
device_code = sys.argv[1] if len(sys.argv) > 1 else 'CH001'
broker = sys.argv[2] if len(sys.argv) > 2 else 'emqx'
port = 1883
username = f"device_{device_code}"
password = 'device_password'
client_id = f"device_{device_code}_{int(time.time())}"

# 全局变量
client = None
running = True

# 设备状态跟踪（收到命令后更新，上报时使用真实状态）
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


def on_connect(client, userdata, flags, rc):
    if rc == 0:
        print("连接成功！\n")
        
        # 订阅命令 Topic
        client.subscribe(f"chambers/{device_code}/command/manual", qos=1)
        print("已订阅命令 Topic")
        
        # 订阅配置 Topic
        client.subscribe(f"chambers/{device_code}/config/auto", qos=1)
        print("已订阅配置 Topic\n")
        
        print("开始定时上报数据（每 60 秒）...")
        print("按 Ctrl+C 停止\n")
    else:
        print(f"连接失败，返回码: {rc}")


def on_message(client, userdata, msg):
    global device_states
    
    topic = msg.topic
    payload = json.loads(msg.payload.decode())
    
    if 'command' in topic:
        print(f"【收到命令】{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"Topic: {topic}")
        print(f"Command ID: {payload.get('command_id')}")
        print(f"Actions: {json.dumps(payload.get('actions', {}))}")
        
        # 执行命令：更新本地设备状态
        actions = payload.get('actions', {})
        executed_actions = {}
        for device, state in actions.items():
            if device in device_states:
                device_states[device] = bool(state)
                executed_actions[device] = bool(state)
                print(f"  → {device}: {'开启' if state else '关闭'}")
        
        # 模拟执行耗时
        time.sleep(1)
        
        # 发送 ACK（包含实际执行的动作）
        ack = {
            'command_id': payload.get('command_id'),
            'status': 'success',
            'executed_at': int(time.time()),
            'executed_actions': executed_actions,
        }
        client.publish(f"chambers/{device_code}/ack", json.dumps(ack), qos=1)
        print("【ACK 已发送】\n")
    
    elif 'config' in topic:
        print(f"【收到配置】{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"Topic: {topic}")
        print(f"Config ID: {payload.get('config_id')}")
        print(f"Control Type: {payload.get('control_type')}")
        print(f"Config: {json.dumps(payload.get('config', {}))}\n")


def on_disconnect(client, userdata, rc):
    print(f"断开连接，返回码: {rc}")


def signal_handler(sig, frame):
    global running
    print("\n正在停止...")
    running = False
    if client:
        client.disconnect()


def generate_sensor_data():
    """生成传感器数据（设备状态使用当前真实状态）"""
    return {
        'timestamp': int(time.time()),
        'temperature': round(20 + random.uniform(-5, 10), 1),
        'humidity': round(60 + random.uniform(-10, 20), 1),
        'co2_level': random.randint(400, 1200),
        'devices': device_states.copy(),  # 使用当前真实设备状态
    }


def main():
    global client, running
    
    # 注册信号处理
    signal.signal(signal.SIGINT, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)
    
    print(f"设备 {device_code} 正在连接到 MQTT Broker ({broker}:{port})...")
    
    # 创建客户端
    client = mqtt.Client(client_id=client_id, clean_session=True)
    client.username_pw_set(username, password)
    client.on_connect = on_connect
    client.on_message = on_message
    client.on_disconnect = on_disconnect
    
    try:
        # 连接
        client.connect(broker, port, keepalive=60)
        client.loop_start()
        
        # 定时上报
        last_report = 0
        while running:
            now = int(time.time())
            if now - last_report >= 60:
                data = generate_sensor_data()
                
                client.publish(
                    f"chambers/{device_code}/data",
                    json.dumps(data),
                    qos=1
                )
                
                # 显示当前设备状态
                active_devices = [k for k, v in device_states.items() if v]
                status_str = ', '.join(active_devices) if active_devices else '全部关闭'
                
                print(
                    f"【数据上报】{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} - "
                    f"温度: {data['temperature']}°C, 湿度: {data['humidity']}%, "
                    f"运行设备: {status_str}"
                )
                
                last_report = now
            
            time.sleep(0.1)
        
        client.loop_stop()
        
    except Exception as e:
        print(f"错误: {e}")
        if client:
            client.disconnect()


if __name__ == '__main__':
    main()
