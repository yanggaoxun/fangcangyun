#!/usr/bin/env python3
"""
设备控制器模块
- GPIO 继电器控制（8路设备开关）
- 设备状态管理（单设备/多设备）
- 状态持久化到文件
- 支持 gpiozero / RPi.GPIO / 模拟模式

新版树莓派（Pi 4/5）推荐使用 gpiozero + pigpio 后端
旧版树莓派（Pi 3）可使用 RPi.GPIO
"""

import json
import threading
import logging
from datetime import datetime

import config

logger = logging.getLogger(__name__)

# ========== GPIO 后端选择 ==========
GPIO_BACKEND = None
GPIO_AVAILABLE = False

# 方案1: gpiozero（推荐，兼容 Pi 4/5）
try:
    from gpiozero import LED
    from gpiozero.pins.pigpio import PiGPIOFactory
    GPIO_BACKEND = 'gpiozero'
    GPIO_AVAILABLE = True
    logger.info("✓ 使用 gpiozero 库")
except ImportError:
    logger.warning("⚠ gpiozero 未安装")

# 方案2: RPi.GPIO（旧版兼容）
if not GPIO_AVAILABLE:
    try:
        import RPi.GPIO as GPIO
        GPIO_BACKEND = 'rpi_gpio'
        GPIO_AVAILABLE = True
        logger.info("✓ 使用 RPi.GPIO 库")
    except ImportError:
        logger.warning("⚠ RPi.GPIO 未安装")

# 方案3: 模拟模式
if not GPIO_AVAILABLE:
    logger.warning("⚠ GPIO 库均不可用，使用模拟模式（仅记录日志，不实际控制硬件）")
    logger.warning("   安装 gpiozero: sudo pip3 install gpiozero pigpio")
    logger.warning("   启动 pigpio: sudo systemctl enable pigpiod --now")


class DeviceController:
    """设备控制器 - 管理 GPIO 继电器和状态"""
    
    def __init__(self):
        self.states = {}
        self.lock = threading.Lock()
        self.gpio_available = GPIO_AVAILABLE
        self.gpio_backend = GPIO_BACKEND
        self.gpio_initialized = False
        self.led_devices = {}  # gpiozero LED 实例字典
        
        # 初始化
        self._init_gpio()
        self._load_states()
    
    def _init_gpio(self):
        """初始化 GPIO"""
        if not self.gpio_available:
            logger.info("GPIO 不可用，使用模拟模式")
            self.states = config.DEFAULT_DEVICE_STATES.copy()
            return
        
        try:
            if self.gpio_backend == 'gpiozero':
                self._init_gpiozero()
            elif self.gpio_backend == 'rpi_gpio':
                self._init_rpi_gpio()
            
            self.gpio_initialized = True
            logger.info(f"✓ GPIO 初始化完成，后端: {self.gpio_backend}")
            logger.info(f"✓ 已配置 {len(config.GPIO_PINS)} 路继电器")
            
        except Exception as e:
            logger.error(f"✗ GPIO 初始化失败: {e}")
            logger.error("   排查: sudo systemctl status pigpiod")
            self.gpio_available = False
            self.states = config.DEFAULT_DEVICE_STATES.copy()
    
    def _init_gpiozero(self):
        """使用 gpiozero 初始化 GPIO"""
        # 检查是否配置了 pigpio 后端
        pigpio_host = getattr(config, 'PIGPIO_HOST', None)
        
        factory = None
        if pigpio_host:
            # 尝试使用 pigpio 后端（更可靠，避免权限问题）
            try:
                factory = PiGPIOFactory(host=pigpio_host)
                logger.debug(f"使用 pigpio 后端 ({pigpio_host})")
            except Exception as e:
                logger.warning(f"pigpio 后端不可用: {e}，使用原生 GPIO")
        else:
            logger.debug("未配置 pigpio，使用原生 GPIO")
        
        # 为每个设备创建 LED 实例
        for device, pin in config.GPIO_PINS.items():
            try:
                if factory:
                    led = LED(pin, pin_factory=factory)
                else:
                    led = LED(pin)
                
                # 初始状态：关闭
                led.off()
                self.led_devices[device] = led
                logger.debug(f"GPIO {pin} 初始化完成: OFF")
            except Exception as e:
                logger.error(f"✗ GPIO {pin} ({device}) 初始化失败: {e}")
    
    def _init_rpi_gpio(self):
        """使用 RPi.GPIO 初始化（旧版兼容）"""
        import RPi.GPIO as GPIO
        
        if config.GPIO_MODE == 'BCM':
            GPIO.setmode(GPIO.BCM)
        else:
            GPIO.setmode(GPIO.BOARD)
        
        GPIO.setwarnings(False)
        
        for device, pin in config.GPIO_PINS.items():
            GPIO.setup(pin, GPIO.OUT)
            GPIO.output(pin, GPIO.LOW)
            logger.debug(f"GPIO {pin} 初始化完成: LOW")
    
    def _load_states(self):
        """从文件加载设备状态（仅在恢复模式下使用）"""
        if config.GPIO_INITIAL_STATE != 'restore':
            # 场景A：上电全部关闭
            self.states = config.DEFAULT_DEVICE_STATES.copy()
            logger.info("✓ 使用默认状态（全部关闭）")
            return
        
        # 场景B：恢复上次状态
        try:
            if config.DEVICE_STATES_FILE.exists():
                with open(config.DEVICE_STATES_FILE, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    loaded = data.get('states', {})
                    with self.lock:
                        self.states.update(loaded)
                
                # 恢复 GPIO 状态
                if self.gpio_available:
                    for device, state in self.states.items():
                        if device in config.GPIO_PINS:
                            self._set_gpio_pin(device, state)
                
                logger.info(f"✓ 已恢复设备状态: {self.states}")
            else:
                logger.info("⚠ 未找到状态文件，使用默认状态")
                self.states = config.DEFAULT_DEVICE_STATES.copy()
        except Exception as e:
            logger.error(f"✗ 加载设备状态失败: {e}")
            self.states = config.DEFAULT_DEVICE_STATES.copy()
    
    def _save_states(self):
        """保存设备状态到文件"""
        try:
            config.CONFIG_DIR.mkdir(parents=True, exist_ok=True)
            
            with self.lock:
                data = {
                    'device_code': config.DEVICE_CODE,
                    'updated_at': datetime.now().isoformat(),
                    'states': self.states.copy(),
                }
            
            # 原子写入
            tmp_file = config.DEVICE_STATES_FILE.with_suffix('.tmp')
            with open(tmp_file, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            
            tmp_file.replace(config.DEVICE_STATES_FILE)
            logger.debug("设备状态已保存")
            
        except Exception as e:
            logger.error(f"✗ 保存设备状态失败: {e}")
    
    def _set_gpio_pin(self, device, state):
        """
        设置 GPIO 引脚电平
        
        NO 继电器逻辑：
        - 高电平 = 继电器线圈通电 = NO触点闭合 = 设备开启
        - 低电平 = 继电器线圈断电 = NO触点断开 = 设备关闭
        
        Args:
            device: 设备名称
            state: True(开启) 或 False(关闭)
        """
        if not self.gpio_available or device not in config.GPIO_PINS:
            return
        
        try:
            if self.gpio_backend == 'gpiozero':
                self._set_gpio_pin_gpiozero(device, state)
            elif self.gpio_backend == 'rpi_gpio':
                self._set_gpio_pin_rpi(device, state)
                
        except Exception as e:
            logger.error(f"✗ GPIO 控制失败 {device}: {e}")
    
    def _set_gpio_pin_gpiozero(self, device, state):
        """使用 gpiozero 设置 GPIO"""
        led = self.led_devices.get(device)
        if not led:
            return
        
        if config.GPIO_RELAY_NO:
            # NO 继电器：on=开启
            if state:
                led.on()
            else:
                led.off()
        else:
            # NC 继电器：off=开启（反向逻辑）
            if state:
                led.off()
            else:
                led.on()
        
        logger.debug(f"{device} -> {'ON' if state else 'OFF'}")
    
    def _set_gpio_pin_rpi(self, device, state):
        """使用 RPi.GPIO 设置 GPIO（旧版兼容）"""
        import RPi.GPIO as GPIO
        
        pin = config.GPIO_PINS[device]
        
        if config.GPIO_RELAY_NO:
            # NO 继电器：HIGH = 开启
            level = GPIO.HIGH if state else GPIO.LOW
        else:
            # NC 继电器：LOW = 开启
            level = GPIO.LOW if state else GPIO.HIGH
        
        GPIO.output(pin, level)
        logger.debug(f"GPIO {pin} -> {'HIGH' if level == GPIO.HIGH else 'LOW'}")
    
    # ========== 公共接口：获取状态 ==========
    
    def get_state(self, device):
        """
        获取单个设备状态
        
        Args:
            device: 设备名称（如 'cooling'）
        
        Returns:
            bool: True(开启) 或 False(关闭)
        """
        with self.lock:
            return self.states.get(device, False)
    
    def get_states(self, devices=None):
        """
        获取设备状态
        
        Args:
            devices: None(获取所有) 或 设备名称列表
        
        Returns:
            dict: 设备状态字典
        """
        with self.lock:
            if devices is None:
                return self.states.copy()
            else:
                return {d: self.states.get(d, False) for d in devices}
    
    # ========== 公共接口：设置状态 ==========
    
    def set_state(self, device, state):
        """
        设置单个设备状态
        
        Args:
            device: 设备名称（如 'cooling'）
            state: True(开启) 或 False(关闭)
        
        Returns:
            bool: 是否设置成功
        """
        try:
            with self.lock:
                if device not in self.states:
                    logger.warning(f"⚠ 未知设备: {device}")
                    return False
                
                # 更新状态
                self.states[device] = bool(state)
                
                # 控制 GPIO（如果有配置）
                if device in config.GPIO_PINS:
                    self._set_gpio_pin(device, bool(state))
                else:
                    logger.debug(f"{device} 无 GPIO 配置，仅更新状态")
            
            # 保存到文件
            self._save_states()
            
            status = '开启' if state else '关闭'
            logger.info(f"✓ {device}: {status}")
            return True
            
        except Exception as e:
            logger.error(f"✗ 设置 {device} 失败: {e}")
            return False
    
    def set_states(self, actions):
        """
        设置多个设备状态
        
        Args:
            actions: dict, 例如 {'cooling': True, 'heating': False}
        
        Returns:
            dict: 实际执行的动作
        """
        executed = {}
        
        try:
            with self.lock:
                for device, state in actions.items():
                    if device not in self.states:
                        logger.warning(f"⚠ 未知设备: {device}")
                        continue
                    
                    # 更新状态
                    self.states[device] = bool(state)
                    executed[device] = bool(state)
                    
                    # 控制 GPIO
                    if device in config.GPIO_PINS:
                        self._set_gpio_pin(device, bool(state))
                    
                    status = '开启' if state else '关闭'
                    logger.info(f"  → {device}: {status}")
            
            # 保存到文件
            self._save_states()
            
            return executed
            
        except Exception as e:
            logger.error(f"✗ 批量设置失败: {e}")
            return executed
    
    def toggle_state(self, device):
        """
        切换单个设备状态
        
        Args:
            device: 设备名称
        
        Returns:
            bool: 切换后的状态
        """
        current = self.get_state(device)
        new_state = not current
        self.set_state(device, new_state)
        return new_state
    
    # ========== 公共接口：执行命令（兼容旧接口） ==========
    
    def execute_actions(self, actions):
        """
        执行设备开关动作（兼容 mqtt_handler 回调）
        
        Args:
            actions: dict, 例如 {'cooling': True, 'heating': False}
        
        Returns:
            dict: 实际执行的动作
        """
        logger.info(f"执行命令: {actions}")
        
        # 模拟执行耗时
        import time
        time.sleep(0.5)
        
        return self.set_states(actions)
    
    # ========== 公共接口：配置管理 ==========
    
    def save_config(self, config_type, config_data, config_id):
        """
        保存自动控制配置到文件
        
        Args:
            config_type: 配置类型（temperature/humidity等）
            config_data: 配置数据
            config_id: 配置ID
        
        Returns:
            bool: 是否保存成功
        """
        try:
            config.CONFIG_DIR.mkdir(parents=True, exist_ok=True)
            
            filename = config.CONFIG_DIR / f"{config_type}.json"
            data = {
                'config_id': config_id,
                'device_code': config.DEVICE_CODE,
                'received_at': datetime.now().isoformat(),
                'control_type': config_type,
                'config': config_data,
            }
            
            # 原子写入
            tmp_file = filename.with_suffix('.tmp')
            with open(tmp_file, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            
            tmp_file.replace(filename)
            logger.info(f"✓ 配置已保存: {filename}")
            return True
            
        except Exception as e:
            logger.error(f"✗ 保存配置失败: {e}")
            return False
    
    # ========== 清理 ==========
    
    def cleanup(self):
        """清理 GPIO 资源"""
        if not self.gpio_available or not self.gpio_initialized:
            return
        
        try:
            # 关闭所有设备
            for device in config.GPIO_PINS:
                self._set_gpio_pin(device, False)
            
            if self.gpio_backend == 'gpiozero':
                # 关闭所有 LED 实例
                for device, led in self.led_devices.items():
                    try:
                        led.close()
                    except Exception:
                        pass
                self.led_devices.clear()
                logger.info("✓ gpiozero GPIO 已清理")
                
            elif self.gpio_backend == 'rpi_gpio':
                import RPi.GPIO as GPIO
                GPIO.cleanup()
                logger.info("✓ RPi.GPIO 已清理")
                
        except Exception as e:
            logger.error(f"✗ GPIO 清理失败: {e}")
