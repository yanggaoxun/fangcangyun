# 自动控制程序说明

## 文件说明

- `auto_control.py` - 自动控制主程序（cron 定时执行）
- `config.py` - 配置文件（常量定义）
- `device_controller.py` - 设备控制器（GPIO + 状态管理）
- `sensor_reader.py` - 传感器读取模块
- `mqtt_handler.py` - MQTT 客户端模块
- `device_worker.py` - 设备工作主程序（长连接守护）

## 光照自动控制

### 支持的模式

1. **定时模式 (auto_schedule)**
   - 在设定的时间段内运行
   - 支持 LED 开/关时长循环控制
   - 配置示例：
     ```json
     {
       "mode": "auto_schedule",
       "is_enabled": true,
       "schedules": [
         {
           "start_time": "06:00",
           "end_time": "22:00",
           "led_on_duration": 30,
           "led_on_unit": "minutes",
           "led_off_duration": 30,
           "led_off_unit": "minutes",
           "is_enabled": true
         }
       ]
     }
     ```

2. **循环模式 (auto_cycle)**
   - 周期性开关控制
   - 配置示例：
     ```json
     {
       "mode": "auto_cycle",
       "is_enabled": true,
       "cycle_run_duration": 30,
       "cycle_run_unit": "minutes",
       "cycle_stop_duration": 30,
       "cycle_stop_unit": "minutes"
     }
     ```

### 注意事项

- 光照控制不支持阈值模式（没有传感器阈值判断）
- 仅支持 schedule 和 cycle 两种模式
- LED 开/关时长支持 minutes（分钟）和 hours（小时）单位

## Crontab 配置

```bash
# 每分钟执行一次自动控制检查
* * * * * /usr/bin/python3 /home/pi/scripts/auto_control.py >> /var/log/auto_control.log 2>&1
```

## 配置文件路径

- 设备状态：`config/device_states.json`
- 光照配置：`config/lighting.json`
- 循环状态：`config/lighting_schedule_cycle.json` / `config/lighting_cycle.json`

## 测试方法

```bash
# 运行一次自动控制
python3 auto_control.py

# 查看日志
tail -f /var/log/auto_control.log
```

## 各控制类型支持的模式

| 控制类型 | 阈值模式 | 定时模式 | 循环模式 |
|---------|---------|---------|---------|
| temperature | ✅ | ✅ | ✅ |
| humidity | ✅ | ✅ | ✅ |
| fresh_air | ✅ | ✅ | ✅ |
| exhaust | ✅ | ✅ | ✅ |
| lighting | ❌ | ✅ | ✅ |
