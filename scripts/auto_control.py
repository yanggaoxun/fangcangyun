#!/usr/bin/env python3
"""
自动控制执行程序
- 根据配置文件的自动控制逻辑执行设备开关
- 支持3种模式：threshold(阈值)/schedule(定时)/cycle(循环)
- 设计为 cron job 定时执行（建议每分钟）
- 光照控制仅支持 schedule(定时) 和 cycle(循环) 模式

Crontab 配置:
    * * * * * /usr/bin/python3 /home/pi/scripts/auto_control.py >> /var/log/auto_control.log 2>&1

控制类型:
    temperature     - 温度控制（制冷/加热）- 支持 schedule
    humidity        - 湿度控制（加湿）- 支持 threshold/schedule/cycle
    fresh_air       - 新风控制 - 支持 threshold/schedule/cycle
    exhaust         - 排风控制 - 支持 threshold/schedule/cycle
    lighting        - 光照控制（LED）- 仅支持 schedule/cycle
"""

import os
import sys
import json
import time
import logging
from datetime import datetime, timedelta
from pathlib import Path

# 确保可以找到同级模块
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import config
from sensor_reader import read_all_data
from device_controller import DeviceController


# ========== 日志配置 ==========
logging.basicConfig(
    level=getattr(logging, config.LOG_LEVEL.upper(), logging.INFO),
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)


# ========== 配置映射 ==========
# 控制类型 -> 对应的设备名称
CONTROL_DEVICE_MAP = {
    'temperature': ['cooling', 'heating'],  # 温度控制制冷和加热
    'humidity': ['humidification'],          # 湿度控制加湿
    'fresh_air': ['fresh_air'],              # 新风控制
    'exhaust': ['exhaust'],                  # 排风控制
    'lighting': ['lighting'],                # 光照控制
}

# 控制类型 -> 传感器字段
CONTROL_SENSOR_MAP = {
    'temperature': 'temperature',
    'humidity': 'humidity',
    'fresh_air': 'co2_level',
    'exhaust': 'co2_level',
    'lighting': None,  # 光照不需要传感器
}

# 各控制类型支持的模式
CONTROL_MODES = {
    'temperature': ['auto_schedule'],  # 温度仅支持定时控制
    'humidity': ['auto_threshold', 'auto_schedule', 'auto_cycle'],
    'fresh_air': ['auto_threshold', 'auto_schedule', 'auto_cycle'],
    'exhaust': ['auto_threshold', 'auto_schedule', 'auto_cycle'],
    'lighting': ['auto_schedule', 'auto_cycle'],  # 光照仅支持 schedule 和 cycle
}


class AutoControl:
    """自动控制执行器"""
    
    def __init__(self):
        self.controller = DeviceController()
        self.sensor_data = None
        self.current_time = datetime.now()
        self.inner_circulation_requests = {}  # 内循环引用计数
        self.inner_circulation_delayed_stop = {}  # 内循环延时关闭时间 {control_type: timestamp}
        self.last_stop_time = {  # 压缩机停止时间记录
            'cooling': 0,
            'heating': 0
        }
        
    def load_control_config(self, control_type):
        """
        加载控制类型的配置
        
        Args:
            control_type: 控制类型（temperature/humidity等）
        
        Returns:
            dict: 配置数据，None表示未启用或不存在
        """
        config_file = config.CONFIG_DIR / f"{control_type}.json"
        
        if not config_file.exists():
            logger.debug(f"未找到 {control_type} 的配置文件")
            return None
        
        try:
            with open(config_file, 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            config_data = data.get('config', {})
            
            # 检查是否启用
            if not config_data.get('is_enabled', False):
                logger.debug(f"{control_type} 自动控制已禁用")
                return None
            
            return config_data
            
        except Exception as e:
            logger.error(f"✗ 读取 {control_type} 配置失败: {e}")
            return None
    
    def read_sensors(self):
        """读取传感器数据"""
        try:
            if config.SERIAL_PORT and os.path.exists(config.SERIAL_PORT):
                self.sensor_data = read_all_data()
                logger.info(f"传感器数据: 温度={self.sensor_data.get('temperature')}°C, "
                          f"湿度={self.sensor_data.get('humidity')}%, "
                          f"CO2={self.sensor_data.get('co2_level')}ppm")
            else:
                logger.warning("未检测到串口设备，无法读取传感器")
                self.sensor_data = None
        except Exception as e:
            logger.error(f"✗ 读取传感器失败: {e}")
            self.sensor_data = None
    
    def _request_inner_circulation(self, control_type, main_device_state, control_config):
        """
        请求内循环（OR逻辑 + 引用计数 + 延时关闭）
        
        逻辑：
        - 记录各控制类型是否需要内循环
        - 只要有任何一个控制类型需要，内循环就开启
        - 所有控制类型都不需要时，开始延时关闭倒计时
        - 延时关闭期间如有新请求，立即重新开启
        
        Args:
            control_type: 控制类型
            main_device_state: 主设备状态（True=开启, False=关闭）
            control_config: 控制配置
        """
        need_inner = False
        
        # 温度控制：强制联动内循环
        if control_type == 'temperature':
            need_inner = main_device_state
            logger.debug(f"【内循环请求】温度控制: {need_inner}")
        
        # 加湿/新风/排风：根据联动配置决定是否联动
        elif control_type in ['humidity', 'fresh_air', 'exhaust']:
            linkage_config = control_config.get('linkage_config', {})
            if linkage_config.get('link_inner_circulation', False):
                need_inner = main_device_state
                logger.debug(f"【内循环请求】{control_type}: {need_inner} (已启用联动)")
            else:
                logger.debug(f"【内循环请求】{control_type}: False (未启用联动)")
        
        # 记录该控制类型的请求状态
        self.inner_circulation_requests[control_type] = need_inner
        
        # 获取延时关闭配置（秒）
        delay_seconds = control_config.get('delay_stop_cycle', 0)
        
        # 如果该控制类型不再需要内循环，设置延时关闭时间
        if not need_inner and delay_seconds > 0:
            # 记录延时关闭时间点
            self.inner_circulation_delayed_stop[control_type] = time.time() + delay_seconds
            logger.debug(f"【内循环延时】{control_type} 请求关闭，将在 {delay_seconds} 秒后关闭")
        elif need_inner:
            # 如果需要内循环，取消该控制类型的延时关闭
            if control_type in self.inner_circulation_delayed_stop:
                del self.inner_circulation_delayed_stop[control_type]
        
        # OR逻辑：只要有任何一个控制类型需要，就开启
        any_need = any(self.inner_circulation_requests.values())
        current_state = self.controller.get_state('inner_circulation')
        
        if any_need and not current_state:
            # 有控制类型需要，立即开启
            requesters = [k for k, v in self.inner_circulation_requests.items() if v]
            logger.info(f"【内循环】开启 (请求者: {requesters})")
            self.controller.set_state('inner_circulation', True)
        elif not any_need and current_state:
            # 所有控制类型都不需要，检查是否有未过期的延时关闭
            now = time.time()
            active_delays = {
                k: v for k, v in self.inner_circulation_delayed_stop.items()
                if v > now
            }
            
            if active_delays:
                # 有延时关闭未过期，保持开启
                remaining = {k: int(v - now) for k, v in active_delays.items()}
                logger.info(f"【内循环】保持开启 (延时关闭中: {remaining})")
            else:
                # 所有延时关闭都已过期，关闭内循环
                logger.info("【内循环】关闭 (无控制类型需要且延时已到期)")
                self.controller.set_state('inner_circulation', False)
                # 清空已过期的延时记录
                self.inner_circulation_delayed_stop.clear()

    def _load_last_mode(self):
        """加载上次温度控制模式"""
        try:
            mode_file = config.CONFIG_DIR / "temperature_mode.json"
            if mode_file.exists():
                with open(mode_file, 'r') as f:
                    data = json.load(f)
                    return data.get('last_mode', 'idle')
        except Exception:
            pass
        return 'idle'
    
    def _save_last_mode(self, mode):
        """保存当前温度控制模式"""
        try:
            config.CONFIG_DIR.mkdir(parents=True, exist_ok=True)
            mode_file = config.CONFIG_DIR / "temperature_mode.json"
            with open(mode_file, 'w') as f:
                json.dump({'last_mode': mode, 'updated_at': datetime.now().isoformat()}, f)
        except Exception as e:
            logger.error(f"保存温度模式失败: {e}")
    
    def _check_compressor_protection(self, target_mode, control_config):
        """
        检查压缩机保护时间
        
        逻辑：
        - 从配置中获取 delay_cooling_heating（秒）
        - 如果目标模式上次停止时间未到保护时间，拒绝启动
        - 返回 (can_start, remaining_seconds)
        
        Args:
            target_mode: 'cooling' 或 'heating'
            control_config: 控制配置
            
        Returns:
            tuple: (bool, int) - (是否可以启动, 剩余等待秒数)
        """
        delay_seconds = control_config.get('delay_cooling_heating', 0)
        
        if delay_seconds <= 0:
            return True, 0  # 未配置保护时间，直接允许
        
        last_stop = self.last_stop_time.get(target_mode, 0)
        if last_stop == 0:
            return True, 0  # 从未停止过，允许启动
        
        elapsed = time.time() - last_stop
        if elapsed >= delay_seconds:
            return True, 0  # 已超过保护时间，允许启动
        
        remaining = int(delay_seconds - elapsed)
        logger.warning(f"【压缩机保护】{target_mode} 停止后仅{int(elapsed)}秒，需等待{remaining}秒才能重新启动")
        return False, remaining
    
    def _record_stop_time(self, mode):
        """记录压缩机停止时间"""
        self.last_stop_time[mode] = time.time()
        logger.info(f"【压缩机保护】记录 {mode} 停止时间")
    
    def _process_temperature_control(self, temp, control_config, mode='threshold'):
        """
        温度控制状态机（带死区记忆）
        
        状态机逻辑：
        - HEATING: 加热中，直到温度 >= heating_upper 停止
        - COOLING: 制冷中，直到温度 <= cooling_lower 停止
        - IDLE: 空闲中，根据上次模式决定下一步动作
          - last=heating: 温度 < heating_lower 才重新加热
          - last=cooling: 温度 > cooling_upper 才重新制冷
        
        Args:
            temp: 当前温度
            control_config: 控制配置
            mode: 'threshold' 或 'schedule'（用于日志区分）
        """
        # 获取阈值配置
        if mode == 'schedule':
            # 定时模式：从schedules获取
            schedules = control_config.get('schedules', [])
            if not schedules:
                logger.warning("温度定时模式: 无时段配置")
                return
            
            current_time = self.current_time.strftime('%H:%M')
            current_schedule = None
            for schedule in schedules:
                if not schedule.get('is_enabled', True):
                    continue
                start_time = schedule.get('start_time', '')[:5]
                end_time = schedule.get('end_time', '')[:5]
                if start_time <= current_time <= end_time:
                    current_schedule = schedule
                    break
            
            if not current_schedule:
                logger.info(f"【温度{mode}】当前 {current_time} 不在时段内，关闭温控")
                self.controller.set_state('cooling', False)
                self.controller.set_state('heating', False)
                self._request_inner_circulation('temperature', False, control_config)
                return
            
            cooling_upper = current_schedule.get('temp_cooling_upper')
            cooling_lower = current_schedule.get('temp_cooling_lower')
            heating_upper = current_schedule.get('temp_heating_upper')
            heating_lower = current_schedule.get('temp_heating_lower')
        else:
            # 阈值模式：直接从control_config获取
            cooling_upper = control_config.get('threshold_upper')
            cooling_lower = control_config.get('threshold_lower')
            heating_upper = control_config.get('heating_upper')
            heating_lower = control_config.get('heating_lower')
        
        # 检查配置完整性
        if None in [cooling_upper, cooling_lower, heating_upper, heating_lower]:
            logger.warning(f"【温度{mode}】配置不完整，跳过")
            return
        
        # 加载上次模式
        last_mode = self._load_last_mode()
        logger.info(f"【温度{mode}】当前温度: {temp}°C, 上次模式: {last_mode}")
        logger.info(f"【温度{mode}】阈值: 制冷{cooling_lower}-{cooling_upper}°C, 加热{heating_lower}-{heating_upper}°C")
        
        # 紧急覆盖：极端温度直接切换模式
        EMERGENCY_HEAT_TEMP = 15  # 低于15度必须加热
        EMERGENCY_COOL_TEMP = 30  # 高于30度必须制冷
        
        if temp < EMERGENCY_HEAT_TEMP and last_mode == 'cooling':
            logger.warning(f"【温度{mode}】紧急: {temp}°C 极低，强制切换为加热")
            last_mode = 'idle'
        elif temp > EMERGENCY_COOL_TEMP and last_mode == 'heating':
            logger.warning(f"【温度{mode}】紧急: {temp}°C 极高，强制切换为制冷")
            last_mode = 'idle'
        
        # 状态机
        if last_mode == 'heating':
            # 上次是加热：继续加热直到达到加热上限
            if temp >= heating_upper:
                logger.info(f"【温度{mode}】{temp}°C >= 加热上限 {heating_upper}°C，停止加热 → 空闲")
                self.controller.set_state('heating', False)
                self.controller.set_state('cooling', False)
                self._record_stop_time('heating')  # 记录加热停止时间
                self._request_inner_circulation('temperature', False, control_config)
                self._save_last_mode('idle')
            else:
                logger.info(f"【温度{mode}】{temp}°C < 加热上限 {heating_upper}°C，继续加热")
                self.controller.set_state('heating', True)
                self.controller.set_state('cooling', False)
                self._request_inner_circulation('temperature', True, control_config)
                self._save_last_mode('heating')
        
        elif last_mode == 'cooling':
            # 上次是制冷：继续制冷直到达到制冷下限
            if temp <= cooling_lower:
                logger.info(f"【温度{mode}】{temp}°C <= 制冷下限 {cooling_lower}°C，停止制冷 → 空闲")
                self.controller.set_state('cooling', False)
                self.controller.set_state('heating', False)
                self._record_stop_time('cooling')  # 记录制冷停止时间
                self._request_inner_circulation('temperature', False, control_config)
                self._save_last_mode('idle')
            else:
                logger.info(f"【温度{mode}】{temp}°C > 制冷下限 {cooling_lower}°C，继续制冷")
                self.controller.set_state('cooling', True)
                self.controller.set_state('heating', False)
                self._request_inner_circulation('temperature', True, control_config)
                self._save_last_mode('cooling')
        
        else:  # idle
            # 空闲状态：根据温度决定下一步
            if temp > cooling_upper:
                # 温度高于制冷上限，检查压缩机保护后开启制冷
                can_start, remaining = self._check_compressor_protection('cooling', control_config)
                if can_start:
                    logger.info(f"【温度{mode}】{temp}°C > 制冷上限 {cooling_upper}°C，开启制冷")
                    self.controller.set_state('cooling', True)
                    self.controller.set_state('heating', False)
                    self._request_inner_circulation('temperature', True, control_config)
                    self._save_last_mode('cooling')
                else:
                    logger.warning(f"【温度{mode}】{temp}°C > 制冷上限 {cooling_upper}°C，但压缩机保护中，还需等待{remaining}秒")
                    # 保持空闲，不开启制冷
                    self.controller.set_state('cooling', False)
                    self.controller.set_state('heating', False)
                    self._request_inner_circulation('temperature', False, control_config)
                    self._save_last_mode('idle')
            elif temp < heating_lower:
                # 温度低于加热下限，检查压缩机保护后开启加热
                can_start, remaining = self._check_compressor_protection('heating', control_config)
                if can_start:
                    logger.info(f"【温度{mode}】{temp}°C < 加热下限 {heating_lower}°C，开启加热")
                    self.controller.set_state('heating', True)
                    self.controller.set_state('cooling', False)
                    self._request_inner_circulation('temperature', True, control_config)
                    self._save_last_mode('heating')
                else:
                    logger.warning(f"【温度{mode}】{temp}°C < 加热下限 {heating_lower}°C，但压缩机保护中，还需等待{remaining}秒")
                    # 保持空闲，不开启加热
                    self.controller.set_state('heating', False)
                    self.controller.set_state('cooling', False)
                    self._request_inner_circulation('temperature', False, control_config)
                    self._save_last_mode('idle')
            else:
                # 在舒适区，保持空闲
                logger.info(f"【温度{mode}】{temp}°C 在舒适区 ({heating_lower}-{cooling_upper}°C)，保持空闲")
                self.controller.set_state('cooling', False)
                self.controller.set_state('heating', False)
                self._request_inner_circulation('temperature', False, control_config)
                self._save_last_mode('idle')
    
    def process_threshold_mode(self, control_type, control_config):
        """
        处理阈值模式
        
        逻辑:
        - temperature: 基于状态机的温度控制（带死区记忆）
        - humidity: 低于下限开加湿，高于上限关加湿（根据配置请求内循环）
        - fresh_air/exhaust: CO2高于上限开，低于下限关（根据配置请求内循环）
        
        内循环采用OR逻辑：只要有任何一个控制类型请求开启，内循环就保持开启
        """
        # 光照不支持阈值模式
        if control_type == 'lighting':
            logger.warning("光照控制不支持阈值模式")
            return
        
        sensor_field = CONTROL_SENSOR_MAP.get(control_type)
        if not sensor_field or not self.sensor_data:
            logger.warning(f"{control_type}: 无传感器数据，跳过阈值控制")
            return
        
        current_value = self.sensor_data.get(sensor_field)
        if current_value is None:
            logger.warning(f"{control_type}: 传感器字段 {sensor_field} 无数据")
            return
        
        upper = control_config.get('threshold_upper')
        lower = control_config.get('threshold_lower')
        
        if upper is None or lower is None:
            logger.warning(f"{control_type}: 阈值配置不完整")
            return
        
        devices = CONTROL_DEVICE_MAP.get(control_type, [])
        
        if control_type == 'temperature':
            # 温度控制：使用状态机（带死区记忆）
            self._process_temperature_control(current_value, control_config, mode='threshold')
        
        elif control_type == 'humidity':
            # 湿度控制：低于下限开加湿（可选联动内循环）
            if current_value < lower:
                logger.info(f"【湿度控制】{current_value}% < 下限 {lower}%，开启加湿")
                self.controller.set_state('humidification', True)
                self._request_inner_circulation('humidity', True, control_config)
            elif current_value > upper:
                logger.info(f"【湿度控制】{current_value}% > 上限 {upper}%，关闭加湿")
                self.controller.set_state('humidification', False)
                self._request_inner_circulation('humidity', False, control_config)
            else:
                logger.info(f"【湿度控制】{current_value}% 在设定范围内，保持当前状态")
        
        elif control_type in ['fresh_air', 'exhaust']:
            # 新风/排风：CO2高于上限开启（可选联动内循环）
            device = devices[0] if devices else control_type
            if current_value > upper:
                logger.info(f"【{control_type}】CO2 {current_value}ppm > 上限 {upper}ppm，开启{device}")
                self.controller.set_state(device, True)
                self._request_inner_circulation(control_type, True, control_config)
            elif current_value < lower:
                logger.info(f"【{control_type}】CO2 {current_value}ppm < 下限 {lower}ppm，关闭{device}")
                self.controller.set_state(device, False)
                self._request_inner_circulation(control_type, False, control_config)
            else:
                logger.info(f"【{control_type}】CO2 {current_value}ppm 在设定范围内，保持当前状态")
    
    def process_schedule_mode(self, control_type, control_config):
        """
        处理定时模式
        
        逻辑:
        - 检查当前时间是否在设定的时段内
        - temperature: 时段内再根据温度阈值判断（请求内循环开启）
        - humidity: 时段内再根据湿度阈值判断（根据配置请求内循环）
        - lighting: 时段内使用 LED 开/关时长循环控制
        - fresh_air/exhaust: 时段内根据CO2阈值判断（根据配置请求内循环）
        """
        schedules = control_config.get('schedules', [])
        
        if not schedules:
            logger.warning(f"{control_type}: 无时段配置")
            return
        
        # 检查当前是否在时段内
        current_time = self.current_time.strftime('%H:%M')
        current_schedule = None
        
        for schedule in schedules:
            if not schedule.get('is_enabled', True):
                continue
            
            start_time = schedule.get('start_time', '')[:5]  # HH:MM
            end_time = schedule.get('end_time', '')[:5]
            
            if start_time <= current_time <= end_time:
                current_schedule = schedule
                break
        
        devices = CONTROL_DEVICE_MAP.get(control_type, [])
        device = devices[0] if devices else control_type
        
        if not current_schedule:
            # 不在任何时段内，关闭设备
            logger.info(f"【{control_type}】当前 {current_time} 不在时段内，关闭{device}")
            self.controller.set_state(device, False)
            # 关闭内循环联动（温度强制，其他根据配置）
            self._request_inner_circulation(control_type, False, control_config)
            return
        
        # 在时段内
        logger.info(f"【{control_type}】当前在时段 {current_schedule.get('start_time', '')} - {current_schedule.get('end_time', '')}")
        
        if control_type == 'lighting':
            # 光照：在时段内使用 LED 开/关时长循环
            self._process_lighting_schedule(device, current_schedule)
        
        elif control_type == 'temperature':
            # 温度控制：使用状态机（带死区记忆）
            if not self.sensor_data:
                logger.warning("温度定时模式: 无传感器数据")
                return
            
            temp = self.sensor_data.get('temperature')
            if temp is None:
                return
            
            self._process_temperature_control(temp, control_config, mode='schedule')
        
        elif control_type == 'humidity':
            # 湿度：时段内根据湿度阈值判断
            if not self.sensor_data:
                logger.warning("湿度定时模式: 无传感器数据")
                return
            
            humidity = self.sensor_data.get('humidity')
            if humidity is None:
                return
            
            humidity_lower = current_schedule.get('humidity_lower')
            humidity_upper = current_schedule.get('humidity_upper')
            
            if humidity_lower and humidity < humidity_lower:
                logger.info(f"【湿度定时】{humidity}% < 下限 {humidity_lower}%，开启加湿")
                self.controller.set_state('humidification', True)
                self._request_inner_circulation('humidity', True, control_config)
            elif humidity_upper and humidity > humidity_upper:
                logger.info(f"【湿度定时】{humidity}% > 上限 {humidity_upper}%，关闭加湿")
                self.controller.set_state('humidification', False)
                self._request_inner_circulation('humidity', False, control_config)
            else:
                logger.info(f"【湿度定时】{humidity}% 在设定范围内，保持当前状态")
        
        elif control_type in ['fresh_air', 'exhaust']:
            # 新风/排风：时段内根据CO2阈值判断
            if not self.sensor_data:
                logger.warning(f"{control_type}定时模式: 无传感器数据")
                return
            
            co2 = self.sensor_data.get('co2_level')
            if co2 is None:
                return
            
            co2_upper = current_schedule.get('co2_upper')
            co2_lower = current_schedule.get('co2_lower')
            
            if co2_upper and co2 > co2_upper:
                logger.info(f"【{control_type}定时】CO2 {co2}ppm > 上限 {co2_upper}ppm，开启{device}")
                self.controller.set_state(device, True)
                self._request_inner_circulation(control_type, True, control_config)
            elif co2_lower and co2 < co2_lower:
                logger.info(f"【{control_type}定时】CO2 {co2}ppm < 下限 {co2_lower}ppm，关闭{device}")
                self.controller.set_state(device, False)
                self._request_inner_circulation(control_type, False, control_config)
            else:
                logger.info(f"【{control_type}定时】CO2 {co2}ppm 在设定范围内，保持当前状态")
    
    def _process_lighting_schedule(self, device, schedule):
        """
        处理光照定时模式的特殊逻辑
        
        在时段内，根据 LED 开/关时长进行循环控制
        """
        # 获取 LED 开/关时长（分钟）
        led_on_duration = schedule.get('led_on_duration', 30)
        led_on_unit = schedule.get('led_on_unit', 'minutes')
        led_off_duration = schedule.get('led_off_duration', 30)
        led_off_unit = schedule.get('led_off_unit', 'minutes')
        
        # 转换为分钟
        led_on_minutes = led_on_duration if led_on_unit == 'minutes' else led_on_duration * 60
        led_off_minutes = led_off_duration if led_off_unit == 'minutes' else led_off_duration * 60
        
        # 读取光照循环状态
        cycle_state_file = config.CONFIG_DIR / "lighting_schedule_cycle.json"
        cycle_state = self._load_cycle_state(cycle_state_file)
        
        current_state = self.controller.get_state(device)
        now = time.time()
        
        if cycle_state.get('next_switch_at', 0) <= now:
            # 到达切换时间
            if current_state:
                # 当前开启，应该关闭
                logger.info(f"【光照定时】LED 开启 {led_on_minutes} 分钟完成，关闭")
                self.controller.set_state(device, False)
                cycle_state['next_switch_at'] = now + (led_off_minutes * 60)
            else:
                # 当前关闭，应该开启
                logger.info(f"【光照定时】LED 关闭 {led_off_minutes} 分钟完成，开启")
                self.controller.set_state(device, True)
                cycle_state['next_switch_at'] = now + (led_on_minutes * 60)
            
            self._save_cycle_state(cycle_state_file, cycle_state)
        else:
            remaining = int(cycle_state['next_switch_at'] - now)
            action = "关闭" if current_state else "开启"
            logger.info(f"【光照定时】LED 当前{'开启' if current_state else '关闭'}，距离{action}还有 {remaining} 秒")
    
    def process_cycle_mode(self, control_type, control_config):
        """
        处理循环模式
        
        逻辑:
        - 根据运行时长和停止时长周期性开关
        - 使用状态文件记录下次切换时间
        """
        run_duration = control_config.get('cycle_run_duration', 30)
        run_unit = control_config.get('cycle_run_unit', 'minutes')
        stop_duration = control_config.get('cycle_stop_duration', 30)
        stop_unit = control_config.get('cycle_stop_unit', 'minutes')
        
        # 转换为秒
        run_seconds = run_duration if run_unit == 'seconds' else run_duration * 60
        if run_unit == 'hours':
            run_seconds = run_duration * 3600
        
        stop_seconds = stop_duration if stop_unit == 'seconds' else stop_duration * 60
        if stop_unit == 'hours':
            stop_seconds = stop_duration * 3600
        
        devices = CONTROL_DEVICE_MAP.get(control_type, [])
        device = devices[0] if devices else control_type
        
        # 读取循环状态
        cycle_state_file = config.CONFIG_DIR / f"{control_type}_cycle.json"
        cycle_state = self._load_cycle_state(cycle_state_file)
        
        current_state = self.controller.get_state(device)
        now = time.time()
        
        if cycle_state.get('next_switch_at', 0) <= now:
            # 到达切换时间
            if current_state:
                # 当前开启，应该停止
                logger.info(f"【{control_type}循环】运行 {run_duration}{run_unit} 完成，停止{device}")
                self.controller.set_state(device, False)
                self._request_inner_circulation(control_type, False, control_config)
                cycle_state['next_switch_at'] = now + stop_seconds
            else:
                # 当前关闭，应该开启
                logger.info(f"【{control_type}循环】停止 {stop_duration}{stop_unit} 完成，开启{device}")
                self.controller.set_state(device, True)
                self._request_inner_circulation(control_type, True, control_config)
                cycle_state['next_switch_at'] = now + run_seconds
            
            self._save_cycle_state(cycle_state_file, cycle_state)
        else:
            remaining = cycle_state['next_switch_at'] - now
            logger.debug(f"【{control_type}循环】距离下次切换还有 {int(remaining)} 秒")
    
    def _load_cycle_state(self, state_file):
        """加载循环状态"""
        try:
            if state_file.exists():
                with open(state_file, 'r') as f:
                    return json.load(f)
        except Exception:
            pass
        return {'next_switch_at': 0}
    
    def _save_cycle_state(self, state_file, state):
        """保存循环状态"""
        try:
            with open(state_file, 'w') as f:
                json.dump(state, f)
        except Exception as e:
            logger.error(f"保存循环状态失败: {e}")
    
    def process_control(self, control_type):
        """
        处理单个控制类型
        
        Args:
            control_type: 控制类型
        """
        logger.info(f"\n{'='*40}")
        logger.info(f"处理 {control_type} 自动控制")
        
        # 加载配置
        control_config = self.load_control_config(control_type)
        if not control_config:
            return
        
        # 获取模式
        mode = control_config.get('mode')
        
        # 检查模式是否支持
        supported_modes = CONTROL_MODES.get(control_type, [])
        if mode not in supported_modes:
            logger.warning(f"{control_type} 不支持 {mode} 模式，支持的模��: {supported_modes}")
            return
        
        if mode == 'auto_threshold':
            logger.info(f"模式: 阈值控制")
            self.process_threshold_mode(control_type, control_config)
        
        elif mode == 'auto_schedule':
            logger.info(f"模式: 定时控制")
            self.process_schedule_mode(control_type, control_config)
        
        elif mode == 'auto_cycle':
            logger.info(f"模式: 循环控制")
            self.process_cycle_mode(control_type, control_config)
        
        else:
            logger.warning(f"未知模式: {mode}")
    
    def run(self):
        """执行所有自动控制"""
        logger.info(f"\n{'='*60}")
        logger.info(f"自动控制执行 - {self.current_time.strftime('%Y-%m-%d %H:%M:%S')}")
        logger.info(f"{'='*60}")
        
        # 重置内循环引用计数（每轮重新计算）
        self.inner_circulation_requests = {}
        
        # 读取传感器数据
        self.read_sensors()
        
        # 处理所有控制类型
        # 内循环采用OR逻辑：只要有任何一个控制类型需要，就保持开启
        control_types = ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting']
        
        for control_type in control_types:
            try:
                self.process_control(control_type)
            except Exception as e:
                logger.error(f"✗ {control_type} 控制执行失败: {e}")
        
        logger.info(f"\n{'='*60}")
        logger.info("自动控制执行完成")
        logger.info(f"当前设备状态: {self.controller.get_states()}")
        logger.info(f"{'='*60}\n")


def main():
    """主函数"""
    try:
        auto_control = AutoControl()
        auto_control.run()
    except Exception as e:
        logger.error(f"✗ 自动控制程序异常: {e}", exc_info=True)
        sys.exit(1)


if __name__ == '__main__':
    main()
