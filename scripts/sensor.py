#!/usr/bin/env python3
"""
传感器测试脚本
- 自动检测串口
- 正确 CRC 计算
- 支持负数温度
- 循环读取显示
"""

import serial
import struct
import time
import glob

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
READ_COUNT = 3  # CO2 + 温度 + 湿度


def calculate_crc(data):
    """CRC16 计算"""
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
    """构建 Modbus RTU 读取请求"""
    request = bytes([
        slave_id, 0x03,
        (start_addr >> 8) & 0xFF, start_addr & 0xFF,
        (register_count >> 8) & 0xFF, register_count & 0xFF
    ])
    crc = calculate_crc(request)
    request += bytes([crc & 0xFF, (crc >> 8) & 0xFF])
    return request


def parse_sensor_data(response, slave_id):
    """解析传感器响应数据"""
    if len(response) < 5 or response[0] != slave_id:
        return None
    if response[1] == 0x83:
        print(f"Modbus 错误码: {response[2]}")
        return None

    # CRC 校验
    received_crc = (response[-1] << 8) | response[-2]
    calculated_crc = calculate_crc(response[:-2])
    if received_crc != calculated_crc:
        print("CRC 校验失败")
        return None

    byte_count = response[2]
    data = response[3:3+byte_count]
    values = [struct.unpack('>H', data[i:i+2])[0] for i in range(0, len(data), 2)]
    return values


def read_sensor(ser):
    """读取传感器数据"""
    request = build_read_request(SLAVE_ID, REG_CO2, READ_COUNT)
    ser.reset_input_buffer()
    ser.reset_output_buffer()
    ser.write(request)
    time.sleep(0.2)
    response = ser.read(3 + READ_COUNT * 2 + 2)  # 11 字节

    if not response or len(response) < 11:
        print(f"响应不完整: {len(response) if response else 0} 字节")
        return None

    values = parse_sensor_data(response, SLAVE_ID)
    if not values or len(values) < 3:
        return None

    co2 = values[0]
    temperature = values[1]
    humidity = values[2]

    # 负数温度处理
    if temperature > 32767:
        temperature -= 65536

    return {
        'co2_level': co2,
        'temperature': round(temperature / 10.0, 1),
        'humidity': round(humidity / 10.0, 1),
    }


def main():
    print("=" * 60)
    print("传感器测试程序")
    print("=" * 60)
    print(f"串口: {SERIAL_PORT}")
    print(f"波特率: {BAUDRATE}")
    print("=" * 60)

    if not AVAILABLE_PORTS:
        print("\n错误: 未找到任何串口设备!")
        return

    try:
        ser = serial.Serial(
            port=SERIAL_PORT,
            baudrate=BAUDRATE,
            bytesize=BYTESIZE,
            parity=PARITY,
            stopbits=STOPBITS,
            timeout=TIMEOUT
        )
        print(f"\n成功打开串口 {SERIAL_PORT}")

        print("\n开始读取传感器数据 (按 Ctrl+C 停止)...")
        while True:
            data = read_sensor(ser)
            if data:
                print(
                    f"🌡 温度：{data['temperature']}℃  "
                    f"💧湿度：{data['humidity']}%  "
                    f"🫧 CO2：{data['co2_level']}ppm"
                )
            else:
                print("✗ 读取失败")
            time.sleep(2)

    except KeyboardInterrupt:
        print("\n\n用户中断")
    except Exception as e:
        print(f"\n错误: {e}")
        import traceback
        traceback.print_exc()
    finally:
        if 'ser' in locals() and ser.is_open:
            ser.close()
            print("串口已关闭")


if __name__ == "__main__":
    main()
