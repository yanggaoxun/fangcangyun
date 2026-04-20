<?php

namespace App\Services;

use App\Models\Chamber;
use App\Models\ChamberControlConfig;
use App\Models\ChamberControlLog;
use App\Models\ChamberControlState;
use Illuminate\Support\Facades\Log;

class ChamberAutoControlService
{
    /**
     * 执行所有方舱的自动控制检查
     */
    public function processAllChambers(): void
    {
        Chamber::all()->each(function (Chamber $chamber) {
            $this->processChamber($chamber->id);
        });
    }

    /**
     * 处理单个方舱的自动控制
     */
    public function processChamber(int $chamberId): void
    {
        $chamber = Chamber::find($chamberId);
        if (! $chamber) {
            return;
        }

        // 获取该方舱所有启用的自动控制配置
        $configs = ChamberControlConfig::where('chamber_id', $chamberId)
            ->where('is_enabled', true)
            ->autoMode()
            ->get();

        foreach ($configs as $config) {
            try {
                $this->processControl($chamber, $config);
            } catch (\Exception $e) {
                Log::error('Auto control error', [
                    'chamber_id' => $chamberId,
                    'control_type' => $config->control_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 处理单个控制类型
     */
    protected function processControl(Chamber $chamber, ChamberControlConfig $config): void
    {
        $state = ChamberControlState::getOrCreate($chamber->id, $config->control_type);

        // 如果被手动覆盖，跳过自动控制
        if ($state->isManualOverride()) {
            return;
        }

        switch ($config->mode) {
            case 'auto_threshold':
                $this->processThresholdMode($chamber, $config, $state);
                break;
            case 'auto_cycle':
                $this->processCycleMode($chamber, $config, $state);
                break;
            case 'auto_schedule':
                $this->processScheduleMode($chamber, $config, $state);
                break;
        }
    }

    /**
     * 处理上下限模式
     */
    protected function processThresholdMode(Chamber $chamber, ChamberControlConfig $config, ChamberControlState $state): void
    {
        $sensorData = $this->getSensorData($chamber->id);
        if (! $sensorData) {
            return;
        }

        $value = $this->getSensorValue($sensorData, $config->threshold_sensor);
        if ($value === null) {
            return;
        }

        $shouldTurnOn = false;
        $shouldTurnOff = false;
        $reason = '';

        // 根据控制类型判断逻辑
        switch ($config->control_type) {
            case 'temperature':
                // 温度控制：高于上限制冷，低于下限加热
                if ($value > $config->threshold_upper) {
                    $shouldTurnOn = true;
                    $reason = "温度{$value}°C超过上限{$config->threshold_upper}°C，启动制冷";
                } elseif ($value < $config->threshold_lower) {
                    $shouldTurnOn = true;
                    $reason = "温度{$value}°C低于下限{$config->threshold_lower}°C，启动加热";
                } else {
                    $shouldTurnOff = true;
                    $reason = "温度{$value}°C在设定范围内，停止温控";
                }
                break;

            case 'humidity':
                // 湿度控制：低于下限加湿
                if ($value < $config->threshold_lower) {
                    $shouldTurnOn = true;
                    $reason = "湿度{$value}%低于下限{$config->threshold_lower}%，启动加湿";
                } elseif ($value > $config->threshold_upper) {
                    $shouldTurnOn = false;
                    $shouldTurnOff = true;
                    $reason = "湿度{$value}%超过上限{$config->threshold_upper}%，停止加湿";
                }
                break;

            case 'fresh_air':
            case 'exhaust':
                // CO2控制：高于上限开启
                if ($value > $config->threshold_upper) {
                    $shouldTurnOn = true;
                    $reason = "CO2浓度{$value}ppm超过上限{$config->threshold_upper}ppm，启动通风";
                } elseif ($value < $config->threshold_lower) {
                    $shouldTurnOff = true;
                    $reason = "CO2浓度{$value}ppm低于下限{$config->threshold_lower}ppm，停止通风";
                }
                break;

            case 'lighting':
                // 光照通常不用阈值模式
                break;
        }

        // 执行控制
        if ($shouldTurnOn && ! $state->current_state) {
            $this->executeControl($chamber, $config, 'turn_on', 'auto', $reason, $sensorData);
        } elseif ($shouldTurnOff && $state->current_state) {
            $this->executeControl($chamber, $config, 'turn_off', 'auto', $reason, $sensorData);
        }
    }

    /**
     * 处理启停循环模式
     */
    protected function processCycleMode(Chamber $chamber, ChamberControlConfig $config, ChamberControlState $state): void
    {
        // 检查是否到达切换时间
        if (! $state->shouldSwitch() && $state->next_switch_at !== null) {
            return;
        }

        // 确定运行时长和停止时长
        $runSeconds = $config->cycle_run_unit === 'seconds'
            ? $config->cycle_run_duration
            : $config->cycle_run_duration * 60;

        $stopSeconds = $config->cycle_stop_unit === 'seconds'
            ? $config->cycle_stop_duration
            : $config->cycle_stop_duration * 60;

        if ($state->current_state) {
            // 当前是开启状态，应该停止
            $this->executeControl($chamber, $config, 'turn_off', 'auto',
                "启停循环模式：运行{$config->cycle_run_duration}{$config->cycle_run_unit}后停止");

            // 设置下次开启时间
            $state->calculateNextSwitchAt($stopSeconds, 'seconds');
        } else {
            // 当前是关闭状态，应该开启
            $this->executeControl($chamber, $config, 'turn_on', 'auto',
                "启停循环模式：停止{$config->cycle_stop_duration}{$config->cycle_stop_unit}后启动");

            // 设置下次停止时间
            $state->calculateNextSwitchAt($runSeconds, 'seconds');
        }
    }

    /**
     * 处理时间段模式
     */
    protected function processScheduleMode(Chamber $chamber, ChamberControlConfig $config, ChamberControlState $state): void
    {
        $schedules = $config->schedules()
            ->where('is_enabled', true)
            ->orderBy('schedule_index')
            ->get();

        $currentSchedule = null;
        $now = now();

        foreach ($schedules as $schedule) {
            if ($schedule->isCurrentTime()) {
                $currentSchedule = $schedule;
                break;
            }
        }

        if (! $currentSchedule) {
            // 不在任何时段内，关闭设备
            if ($state->current_state) {
                $this->executeControl($chamber, $config, 'turn_off', 'auto', '不在有效时段内，停止运行');
            }

            return;
        }

        // 在时段内，根据当前使用的控制逻辑判断
        switch ($config->control_type) {
            case 'temperature':
                // 使用时段配置的温度阈值
                $sensorData = $this->getSensorData($chamber->id);
                $temp = $sensorData?->temperature;

                if ($temp !== null) {
                    if ($temp > $currentSchedule->temp_cooling_upper) {
                        if (! $state->current_state) {
                            $this->executeControl($chamber, $config, 'turn_on', 'auto',
                                "时段{$currentSchedule->schedule_index}：温度{$temp}°C超过上限{$currentSchedule->temp_cooling_upper}°C");
                        }
                    } elseif ($temp < $currentSchedule->temp_heating_lower) {
                        if (! $state->current_state) {
                            $this->executeControl($chamber, $config, 'turn_on', 'auto',
                                "时段{$currentSchedule->schedule_index}：温度{$temp}°C低于下限{$currentSchedule->temp_heating_lower}°C");
                        }
                    } else {
                        if ($state->current_state) {
                            $this->executeControl($chamber, $config, 'turn_off', 'auto',
                                "时段{$currentSchedule->schedule_index}：温度在设定范围内");
                        }
                    }
                }
                break;

            case 'humidity':
                $sensorData = $this->getSensorData($chamber->id);
                $humidity = $sensorData?->humidity;

                if ($humidity !== null) {
                    if ($humidity < $currentSchedule->humidity_lower && ! $state->current_state) {
                        $this->executeControl($chamber, $config, 'turn_on', 'auto',
                            "时段{$currentSchedule->schedule_index}：湿度{$humidity}%低于下限");
                    } elseif ($humidity > $currentSchedule->humidity_upper && $state->current_state) {
                        $this->executeControl($chamber, $config, 'turn_off', 'auto',
                            "时段{$currentSchedule->schedule_index}：湿度{$humidity}%超过上限");
                    }
                }
                break;

            case 'fresh_air':
            case 'exhaust':
                $sensorData = $this->getSensorData($chamber->id);
                $co2 = $sensorData?->co2_level;

                if ($co2 !== null) {
                    if ($co2 > $currentSchedule->co2_upper && ! $state->current_state) {
                        $this->executeControl($chamber, $config, 'turn_on', 'auto',
                            "时段{$currentSchedule->schedule_index}：CO2{$co2}ppm超过上限");
                    } elseif ($co2 < $currentSchedule->co2_lower && $state->current_state) {
                        $this->executeControl($chamber, $config, 'turn_off', 'auto',
                            "时段{$currentSchedule->schedule_index}：CO2{$co2}ppm低于下限");
                    }
                }
                break;

            case 'lighting':
                // 光照：在时段内开启
                if (! $state->current_state) {
                    $this->executeControl($chamber, $config, 'turn_on', 'auto',
                        "时段{$currentSchedule->schedule_index}：{$currentSchedule->start_time->format('H:i')} - {$currentSchedule->end_time->format('H:i')}");
                }
                break;
        }
    }

    /**
     * 执行控制动作
     */
    protected function executeControl(
        Chamber $chamber,
        ChamberControlConfig $config,
        string $action,
        string $triggerType,
        string $reason,
        ?EnvironmentData $sensorData = null
    ): void {
        $state = ChamberControlState::getOrCreate($chamber->id, $config->control_type);
        $deviceName = ChamberControlConfig::CONTROL_TYPES[$config->control_type] ?? $config->control_type;

        // 检查延时
        if ($config->delay_seconds > 0 && $triggerType === 'auto') {
            // TODO: 实现延时队列逻辑
        }

        // 执行主设备控制
        $state->setDeviceState(
            $action === 'turn_on',
            $triggerType === 'auto' ? 'auto' : 'manual',
            $triggerType === 'manual'
        );

        // 记录日志
        ChamberControlLog::record(
            chamberId: $chamber->id,
            controlType: $config->control_type,
            triggerType: $triggerType,
            action: $action,
            reason: $reason,
            sensorData: $sensorData?->toArray(),
            configSnapshot: $config->toArray()
        );

        // 处理联动
        $this->handleLinkage($chamber, $config, $action, $triggerType, $reason);

        Log::info('Auto control executed', [
            'chamber_id' => $chamber->id,
            'control_type' => $config->control_type,
            'action' => $action,
            'reason' => $reason,
        ]);
    }

    /**
     * 处理联动控制
     */
    protected function handleLinkage(
        Chamber $chamber,
        ChamberControlConfig $config,
        string $action,
        string $triggerType,
        string $reason
    ): void {
        $linkage = $config->getLinkageConfig();

        // 新风/排风/加湿/制冷/加热 联动内循环
        if ($linkage['link_inner_circulation'] ?? true) {
            if (in_array($config->control_type, ['fresh_air', 'exhaust', 'humidity', 'temperature'])) {
                $innerState = ChamberControlState::getOrCreate($chamber->id, 'inner_circulation');
                $innerConfig = ChamberControlConfig::getOrCreate($chamber->id, 'inner_circulation');

                if ($action === 'turn_on' && ! $innerState->current_state) {
                    $innerState->setDeviceState(true, $triggerType === 'auto' ? 'auto' : 'manual', $triggerType === 'manual');
                    ChamberControlLog::record(
                        chamberId: $chamber->id,
                        controlType: 'inner_circulation',
                        triggerType: 'linkage',
                        action: 'turn_on',
                        reason: "联动：{$reason}",
                    );
                }
            }
        }

        // 排风联动新风
        if (($linkage['link_fresh_air'] ?? false) && $config->control_type === 'exhaust') {
            $freshState = ChamberControlState::getOrCreate($chamber->id, 'fresh_air');
            if ($action === 'turn_on' && ! $freshState->current_state) {
                $freshState->setDeviceState(true, 'linkage', false);
                ChamberControlLog::record(
                    chamberId: $chamber->id,
                    controlType: 'fresh_air',
                    triggerType: 'linkage',
                    action: 'turn_on',
                    reason: "联动排风：{$reason}",
                );
            }
        }

        // 新风联动排风
        if (($linkage['link_exhaust'] ?? false) && $config->control_type === 'fresh_air') {
            $exhaustState = ChamberControlState::getOrCreate($chamber->id, 'exhaust');
            if ($action === 'turn_on' && ! $exhaustState->current_state) {
                $exhaustState->setDeviceState(true, 'linkage', false);
                ChamberControlLog::record(
                    chamberId: $chamber->id,
                    controlType: 'exhaust',
                    triggerType: 'linkage',
                    action: 'turn_on',
                    reason: "联动新风：{$reason}",
                );
            }
        }
    }

    /**
     * 获取传感器数据
     */
    protected function getSensorData(int $chamberId): ?ChamberManualControl
    {
        return ChamberManualControl::where('chamber_id', $chamberId)
            ->latest('recorded_at')
            ->first();
    }

    /**
     * 获取传感器值
     */
    protected function getSensorValue(ChamberManualControl $data, string $sensor): ?float
    {
        return match ($sensor) {
            'temperature' => $data->temperature,
            'humidity' => $data->humidity,
            'co2' => $data->co2_level,
            default => null,
        };
    }

    /**
     * 手动控制设备
     */
    public function manualControl(
        int $chamberId,
        string $controlType,
        bool $turnOn,
        ?int $userId = null,
        ?int $overrideMinutes = null
    ): void {
        $config = ChamberControlConfig::getOrCreate($chamberId, $controlType);
        $state = ChamberControlState::getOrCreate($chamberId, $controlType);

        // 检查模式
        if ($config->mode === 'off') {
            throw new \RuntimeException('该设备已关闭，无法手动控制');
        }

        $deviceName = ChamberControlConfig::CONTROL_TYPES[$controlType] ?? $controlType;
        $action = $turnOn ? 'turn_on' : 'turn_off';

        // 更新状态（带手动覆盖标记）
        $state->setDeviceState(
            $turnOn,
            'manual',
            true,
            $overrideMinutes
        );

        // 记录日志
        ChamberControlLog::record(
            chamberId: $chamberId,
            controlType: $controlType,
            triggerType: 'manual',
            action: $action,
            reason: "用户手动{$action}",
            executedBy: $userId
        );

        // 处理联动
        $this->handleLinkage(
            Chamber::find($chamberId),
            $config,
            $action,
            'manual',
            "用户手动{$action}"
        );
    }
}
