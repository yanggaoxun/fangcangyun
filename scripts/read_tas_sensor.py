#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
树莓派读取 TAS-WSCO2-R0X000-外置 温湿度CO2传感器
RS485 USB + Modbus RTU协议 + MQTT上报
适用于 Raspberry Pi

用法:
    python3 read_tas_sensor.py                    # 仅本地显示
    python3 read_tas_sensor.py --mqtt             # 读取并上报到MQTT
    python3 read_tas_sensor.py --mqtt --broker 192.168.1.10  # 指定broker
    python3 read_tas_sensor.py --mqtt --device CH003          # 指定设备编码
"""

import serial
import struct
import time
import glob
import sys
import json
import argparse

try:
    import paho.mqtt.client as mqtt
    MQTT_AVAILABLE = True
except ImportError:
    MQTT_AVAILABLE = False
    print("警告: paho-mqtt未安装，MQTT功能不可用")
    print("安装: pip3 install paho-mqtt")

# ====== 传感器配置 ======
AVAILABLE_PORTS = glob.glob('/dev/ttyUSB*') + glob.glob('/dev/ttyACM*')
SERIAL_PORT = AVAILABLE_PORTS[0] if AVAILABLE_PORTS else '/dev/ttyUSB0'
BAUDRATE = 9600
BYTESIZE = 8
PARITY = 'N'
STOPBITS = 1
SLAVE_ID = 1
TIMEOUT = 2

REG_CO2 = 0x0000
REG_TEMPERATURE = 0x0001
REG_HUMIDITY = 0x0002
READ_COUNT = 3

# ====== MQTT配置 ======
DEVICE_CODE = 'CH003'  # 设备编码常量
MQTT_BROKER = '39.102.54.120'
MQTT_PORT = 1883
MQTT_USERNAME = 'device'
MQTT_PASSWORD = 'device_password'


def calculate_crc(data):
    crc = 0xFFFF
    for byte in data:
        crc ^= byte
        for _ in range(8):
            if crc & 0x0001:
                crc = (crc >> 1) ^ 0xA001
            else:
                crc >>= 1
    return crc


def build_read_request(slave_id, start_addr, register_count):
    request = bytes([
        slave_id, 0x03,
        (start_addr >> 8) & 0xFF, start_addr & 0xFF,
        (register_count >> 8) & 0xFF, register_count & 0xFF
    ])
    crc = calculate_crc(request)
    request += bytes([crc & 0xFF, (crc >> 8) & 0xFF])
    return request


def verify_crc(data):
    if len(data) < 5:
        return False
    received_crc = (data[-1] << 8) | data[-2]
    return received_crc == calculate_crc(data[:-2])


def parse_sensor_data(response, slave_id):
    if len(response) < 5 or response[0] != slave_id:
        return None
    if response[1] == 0x83:
        print(f"Modbus错误码: {response[2]}")
        return None
    if not verify_crc(response):
        print("CRC校验失败")
        return None
    byte_count = response[2]
    data = response[3:3+byte_count]
    return [struct.unpack('>H', data[i:i+2])[0] for i in range(0, len(data), 2)]


def read_all_data(ser):
    request = build_read_request(SLAVE_ID, REG_CO2, READ_COUNT)
    ser.reset_input_buffer()
    ser.reset_output_buffer()
    ser.write(request)
    time.sleep(0.2)
    response = ser.read(3 + READ_COUNT * 2 + 2)
    
    if not response or len(response) < 3 + READ_COUNT * 2 + 2:
        print(f"响应不完整: {len(response) if response else 0}字节")
        return None
    
    values = parse_sensor_data(response, SLAVE_ID)
    if not values or len(values) < 3:
        return None
    
    co2 = values[0]
    temperature = values[1]
    humidity = values[2]
    
    if temperature > 32767:
        temperature -= 65536
    
    return {
        'co2_level': co2,
        'temperature': round(temperature / 10.0, 1),
        'humidity': round(humidity / 10.0, 1),
        'timestamp': int(time.time())
    }


def publish_mqtt(data, device_code, broker):
    """通过MQTT上报数据"""
    if not MQTT_AVAILABLE:
        print("错误: paho-mqtt未安装，无法上报")
        return False
    
    try:
        client_id = f"sensor_{device_code}_{int(time.time())}"
        client = mqtt.Client(
            callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
            client_id=client_id
        )
        client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)
        
        client.connect(broker, MQTT_PORT, keepalive=60)
        client.loop_start()
        
        result = client.publish(
            f"chambers/{device_code}/data",
            json.dumps(data),
            qos=1
        )
        
        result.wait_for_publish(timeout=5)
        
        success = result.rc == mqtt.MQTT_ERR_SUCCESS
        
        client.loop_stop()
        client.disconnect()
        
        return success
        
    except Exception as e:
        print(f"MQTT上报错误: {e}")
        return False


def main():
    parser = argparse.ArgumentParser(description='TAS-WSCO2传感器读取程序')
    parser.add_argument('--mqtt', action='store_true', help='启用MQTT上报')
    parser.add_argument('--broker', default=MQTT_BROKER, help='MQTT Broker地址')
    args = parser.parse_args()
    
    print("=" * 60)
    print("TAS-WSCO2 传感器读取程序")
    print("=" * 60)
    print(f"串口: {SERIAL_PORT}")
    print(f"波特率: {BAUDRATE}")
    print(f"设备地址: {SLAVE_ID}")
    print(f"设备编码: {DEVICE_CODE}")
    if args.mqtt:
        print(f"MQTT Broker: {args.broker}")
    print("=" * 60)
    
    if not AVAILABLE_PORTS:
        print("\n错误: 未找到任何串口设备!")
        sys.exit(1)
    
    serial_port = SERIAL_PORT
    if serial_port not in AVAILABLE_PORTS:
        serial_port = AVAILABLE_PORTS[0]
    
    try:
        ser = serial.Serial(
            port=serial_port,
            baudrate=BAUDRATE,
            bytesize=BYTESIZE,
            parity=PARITY,
            stopbits=STOPBITS,
            timeout=TIMEOUT
        )
        
        print(f"\n成功打开串口 {serial_port}")
        

        print("\n读取传感器数据...")
        data = read_all_data(ser)
		
        if data:
            print(f"\n✓ CO2浓度: {data['co2_level']} ppm")
            print(f"✓ 温度: {data['temperature']} °C")
            print(f"✓ 湿度: {data['humidity']} %RH")
			
            if args.mqtt:
                print(f"\n正在上报到MQTT...")
                if publish_mqtt(data, DEVICE_CODE, args.broker):
                    print("✓ MQTT上报成功")
                else:
                    print("✗ MQTT上报失败")
        else:
            print("\n✗ 读取失败")
        
        ser.close()
        print("\n串口已关闭")
        
    except Exception as e:
        print(f"\n错误: {e}")
        import traceback
        traceback.print_exc()


if __name__ == "__main__":
    main()
