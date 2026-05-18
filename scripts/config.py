#!/usr/bin/env python3
"""
设备工作程序配置文件
所有可调整的常量都放在这里
"""

import os
from pathlib import Path

# ========== 设备标识 ==========
DEVICE_CODE = 'CH003'  # 设备编码，根据实际设备修改

# ========== MQTT 配置 ==========
MQTT_BROKER = 'emqx'  # MQTT Broker IP，根据实际服务器修改
MQTT_PORT = 1883
MQTT_USERNAME = 'device'
MQTT_PASSWORD = 'device_password'
MQTT_KEEPALIVE = 60  # 心跳间隔（秒）
MQTT_QOS = 1  # 消息质量等级

# ========== 上报配置 ==========
# 传感器数据上报间隔（秒）
# 建议值：
#   60  = 1分钟（默认，适合调试）
#   300 = 5分钟（生产环境推荐）
#   600 = 10分钟（省流量模式）
REPORT_INTERVAL = 60

# 状态上报间隔（秒），上报设备在线状态
STATUS_REPORT_INTERVAL = 300  # 每5分钟上报一次在线状态

# ========== 传感器配置 ==========
# 串口配置（RS485 USB 转串口）
SERIAL_PORT = '/dev/ttyUSB0'
SERIAL_BAUDRATE = 9600
SERIAL_BYTESIZE = 8
SERIAL_PARITY = 'N'
SERIAL_STOPBITS = 1
SERIAL_TIMEOUT = 2

# Modbus 从机地址
MODBUS_SLAVE_ID = 1

# 传感器寄存器地址
REG_CO2 = 0x0000
REG_TEMPERATURE = 0x0001
REG_HUMIDITY = 0x0002
REG_READ_COUNT = 3  # 连续读取3个寄存器

# ========== 文件路径配置 ==========
# 配置文件存放目录
CONFIG_DIR = Path('./config')

# 设备状态文件
DEVICE_STATES_FILE = CONFIG_DIR / 'device_states.json'

# 日志文件
LOG_DIR = Path('/var/log') if os.access('/var/log', os.W_OK) else CONFIG_DIR
LOG_FILE = LOG_DIR / f'device_{DEVICE_CODE}.log'

# ========== GPIO 配置 ==========
# GPIO 引脚映射（BCM 编码）
# NO 继电器接线方式：GPIO.HIGH = 继电器吸合 = 设备开启
# 树莓派 BCM 编码引脚：
#   inner_circulation  -> GPIO 17 (Pin 11)
#   cooling            -> GPIO 18 (Pin 12)
#   heating            -> GPIO 27 (Pin 13)
#   fan                -> GPIO 22 (Pin 15)
#   fresh_air          -> GPIO 23 (Pin 16)
#   humidification     -> GPIO 24 (Pin 18)
#   lighting_supplement-> GPIO 25 (Pin 22)
#   lighting           -> GPIO  5 (Pin 29)
GPIO_PINS = {
    'inner_circulation': 17,
    'cooling': 18,
    'heating': 27,
    'fan': 22,
    'fresh_air': 23,
    'humidification': 24,
    'lighting_supplement': 25,
    'lighting': 5,
}

# pigpio 守护进程配置（可选，仅 gpiozero 使用）
# 作用: 提供更可靠的 GPIO 控制，支持远程 GPIO
# 用法:
#   None      = 不使用 pigpio，直接使用 gpiozero 原生后端（默认，最简单）
#   'localhost' = 连接本地 pigpio 守护进程（需要先启动: sudo systemctl start pigpiod）
#   '192.168.x.x' = 连接远程树莓派的 pigpio 守护进程
PIGPIO_HOST = None

# GPIO 模式：'BCM' 或 'BOARD'
GPIO_MODE = 'BCM'

# NO 继电器逻辑配置
# True:  GPIO.HIGH = 继电器吸合(NO闭合) = 设备开启
# False: GPIO.LOW  = 继电器释放(NO断开) = 设备关闭
GPIO_RELAY_NO = True

# 上电初始状态：'off'(全部关闭) 或 'restore'(恢复上次状态)
GPIO_INITIAL_STATE = 'off'

# ========== 设备状态默认值 ==========
# 所有可控设备的默认状态（关闭）
# 注意：four_way_valve 不在 GPIO_PINS 中，仅作为软件状态管理
DEFAULT_DEVICE_STATES = {
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

# ========== 重连配置 ==========
# MQTT 断线重连配置
RECONNECT_MIN_DELAY = 1   # 最小重连间隔（秒）
RECONNECT_MAX_DELAY = 30  # 最大重连间隔（秒）
MAX_RECONNECT_COUNT = 100  # 最大重连次数

# ========== 日志配置 ==========
LOG_LEVEL = 'INFO'  # DEBUG / INFO / WARNING / ERROR
LOG_FORMAT = '%(asctime)s [%(levelname)s] %(message)s'
