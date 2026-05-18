#!/usr/bin/env python3
"""
树莓派 GPIO 继电器控制测试程序
兼容新版树莓派系统（Pi 4/5）
支持 gpiozero 和 RPi.GPIO 两种库

用法:
    python3 gpio.py                    # 测试 GPIO17
    python3 gpio.py --pin 18           # 测试指定引脚
    sudo python3 gpio.py               # 使用 root 权限运行
"""

import time
import sys
import argparse

# 解析命令行参数
parser = argparse.ArgumentParser(description='树莓派 GPIO 继电器控制测试')
parser.add_argument('--pin', type=int, default=17, help='GPIO 引脚号 (默认: 17)')
parser.add_argument('--on-time', type=int, default=5, help='开启时长秒数 (默认: 5)')
parser.add_argument('--off-time', type=int, default=5, help='关闭时长秒数 (默认: 5)')
args = parser.parse_args()

RELAY_PIN = args.pin

# 尝试使用 gpiozero（推荐，新版系统兼容性好）
try:
    from gpiozero import LED
    from gpiozero.pins.pigpio import PiGPIOFactory
    
    print("✓ 使用 gpiozero 库")
    
    # 尝试使用 pigpio 后端（更可靠）
    try:
        factory = PiGPIOFactory()
        relay = LED(RELAY_PIN, pin_factory=factory)
        print("✓ 使用 pigpio 后端")
    except Exception:
        relay = LED(RELAY_PIN)
        print("✓ 使用原生 GPIO 后端")
    
    def set_relay(state):
        if state:
            relay.on()
        else:
            relay.off()
    
    def get_relay_state():
        return relay.is_lit
    
    def cleanup_gpio():
        relay.close()
        print("✓ GPIO 资源已释放")

# 回退到 RPi.GPIO
except ImportError:
    print("⚠ gpiozero 未安装，尝试使用 RPi.GPIO")
    print("  建议安装: sudo pip3 install gpiozero pigpio")
    
    try:
        import RPi.GPIO as GPIO
        
        GPIO.setmode(GPIO.BCM)
        GPIO.setwarnings(False)
        GPIO.setup(RELAY_PIN, GPIO.OUT)
        
        def set_relay(state):
            GPIO.output(RELAY_PIN, GPIO.HIGH if state else GPIO.LOW)
        
        def get_relay_state():
            return GPIO.input(RELAY_PIN) == GPIO.HIGH
        
        def cleanup_gpio():
            GPIO.cleanup()
            print("✓ GPIO 资源已释放")
            
    except Exception as e:
        print(f"\n✗ GPIO 初始化失败: {e}")
        print("\n排查步骤:")
        print("1. 确认在树莓派上运行（不是 Docker/虚拟机）")
        print("2. 使用 sudo 运行: sudo python3 gpio.py")
        print("3. 安装 gpiozero: sudo pip3 install gpiozero pigpio")
        print("4. 启用 pigpio 守护进程: sudo systemctl enable pigpiod --now")
        print("5. 检查用户权限: sudo usermod -a -G gpio $USER")
        sys.exit(1)

# 主程序
print("\n" + "=" * 60)
print("树莓派继电器控制测试程序")
print("=" * 60)
print(f"引脚: GPIO{RELAY_PIN} (BCM 编码)")
print(f"开启时长: {args.on_time} 秒")
print(f"关闭时长: {args.off_time} 秒")
print("=" * 60)
print("按 Ctrl+C 退出程序\n")

try:
    cycle = 0
    while True:
        cycle += 1
        print(f"--- 循环 #{cycle} ---")
        
        # 开启继电器
        set_relay(True)
        state = get_relay_state()
        print(f"✅ 继电器开启 (GPIO{RELAY_PIN}={int(state)})")
        time.sleep(args.on_time)
        
        # 关闭继电器
        set_relay(False)
        state = get_relay_state()
        print(f"❌ 继电器关闭 (GPIO{RELAY_PIN}={int(state)})\n")
        time.sleep(args.off_time)

except KeyboardInterrupt:
    print("\n\n用户中断程序")
    
    # 确保继电器关闭
    set_relay(False)
    print("✓ 继电器已关闭")
    
    # 清理 GPIO
    cleanup_gpio()
    
    print("\n程序已安全退出")
    sys.exit(0)

except Exception as e:
    print(f"\n✗ 发生错误: {e}")
    set_relay(False)
    cleanup_gpio()
    sys.exit(1)
