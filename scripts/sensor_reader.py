#!/usr/bin/env python3
"""
传感器读取模块
- 读取 TAS-WSCO2 温湿度CO2传感器（RS485 + Modbus RTU）
- 支持 GPIO 读取设备开关状态（预留接口）
"""

import struct
import time
import glob
import logging

import config

logger = logging.getLogger(__name__)

# 尝试导入串口库
try:
    import serial
    SERIAL_AVAILABLE = True
except ImportError:
    SERIAL_AVAILABLE = False
    logger.warning("警告: pyserial 未安装，将使用模拟数据")
    logger.warning("安装: pip3 install pyserial")


def calculate_crc(data):
    """计算 Modbus CRC16"""
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
    """构建 Modbus 读取请求"""
    request = bytes([
        slave_id, 0x03,
        (start_addr >> 8) & 0xFF, start_addr & 0xFF,
        (register_count >> 8) & 0xFF, register_count & 0xFF
    ])
    crc = calculate_crc(request)
    request += bytes([crc & 0xFF, (crc >> 8) & 0xFF])
    return request


def verify_crc(data):
    """验证 CRC"""
    if len(data) < 5:
        return False
    received_crc = (data[-1] << 8) | data[-2]
    return received_crc == calculate_crc(data[:-2])


def parse_sensor_data(response, slave_id):
    """解析传感器数据"""
    if len(response) < 5 or response[0] != slave_id:
        return None
    if response[1] == 0x83:
        logger.error(f"Modbus 错误码: {response[2]}")
        return None
    if not verify_crc(response):
        logger.error("CRC 校验失败")
        return None
    
    byte_count = response[2]
    data = response[3:3+byte_count]
    return [struct.unpack('>H', data[i:i+2])[0] for i in range(0, len(data), 2)]


def read_modbus_sensor():
    """
    通过 Modbus RTU 读取传感器数据
    
    Returns:
        dict: 包含 co2_level, temperature, humidity 的字典
        None: 读取失败
    """
    if not SERIAL_AVAILABLE:
        logger.error("串口库不可用，无法读取传感器")
        return None
    
    # 查找可用串口
    available_ports = glob.glob('/dev/ttyUSB*') + glob.glob('/dev/ttyACM*')
    if not available_ports:
        logger.error("未找到任何串口设备")
        return None
    
    serial_port = available_ports[0]
    
    try:
        ser = serial.Serial(
            port=serial_port,
            baudrate=config.SERIAL_BAUDRATE,
            bytesize=config.SERIAL_BYTESIZE,
            parity=config.SERIAL_PARITY,
            stopbits=config.SERIAL_STOPBITS,
            timeout=config.SERIAL_TIMEOUT
        )
        
        # 构建读取请求
        request = build_read_request(
            config.MODBUS_SLAVE_ID,
            config.REG_CO2,
            config.REG_READ_COUNT
        )
        
        # 清空缓冲区并发送请求
        ser.reset_input_buffer()
        ser.reset_output_buffer()
        ser.write(request)
        time.sleep(0.2)
        
        # 读取响应
        expected_length = 3 + config.REG_READ_COUNT * 2 + 2
        response = ser.read(expected_length)
        
        ser.close()
        
        if not response or len(response) < expected_length:
            logger.error(f"响应不完整: {len(response) if response else 0} 字节")
            return None
        
        # 解析数据
        values = parse_sensor_data(response, config.MODBUS_SLAVE_ID)
        if not values or len(values) < 3:
            return None
        
        co2 = values[0]
        temperature = values[1]
        humidity = values[2]
        
        # 处理有符号温度
        if temperature > 32767:
            temperature -= 65536
        
        return {
            'co2_level': co2,
            'temperature': round(temperature / 10.0, 1),
            'humidity': round(humidity / 10.0, 1),
        }
        
    except Exception as e:
        logger.error(f"读取传感器失败: {e}")
        return None


def read_device_states():
    """
    读取设备开关状态（预留 GPIO 接口）
    
    当前实现：
    - 从配置文件读取状态（由 device_subscriber 或手动设置）
    
    将来实现：
    - 通过 GPIO 读取继电器实际状态
    
    Returns:
        dict: 设备状态字典
    """
    # TODO: 将来通过 GPIO 读取实际状态
    # 当前返回默认状态（后续可从 device_states.json 读取）
    return config.DEFAULT_DEVICE_STATES.copy()


def read_all_data():
    """
    读取所有数据（传感器 + 设备状态）
    
    Returns:
        dict: 完整的数据字典，包含 timestamp
    """
    # 读取传感器数据
    sensor_data = read_modbus_sensor()
    
    if sensor_data is None:
        # 传感器读取失败，返回错误标记
        logger.warning("传感器读取失败，使用空数据上报")
        sensor_data = {
            'co2_level': None,
            'temperature': None,
            'humidity': None,
        }
    
    # 读取设备状态
    device_states = read_device_states()
    
    # 组合数据
    data = {
        'timestamp': int(time.time()),
        **sensor_data,
        'devices': device_states,
    }
    
    return data


def generate_mock_data():
    """
    生成模拟数据（用于测试，无传感器时）
    
    Returns:
        dict: 模拟的传感器数据
    """
    import random
    
    return {
        'timestamp': int(time.time()),
        'co2_level': random.randint(400, 1200),
        'temperature': round(20 + random.uniform(-5, 10), 1),
        'humidity': round(60 + random.uniform(-10, 20), 1),
        'devices': config.DEFAULT_DEVICE_STATES.copy(),
    }
