<?php

namespace App\Console\Commands;

use App\Models\Chamber;
use App\Models\ChamberControlConfig;
use App\Models\ChamberControlLog;
use App\Models\ChamberControlState;
use App\Models\ChamberSchedule;
use App\Models\User;
use Illuminate\Console\Command;

class SeedAutoControlTestData extends Command
{
    protected $signature = 'auto-control:seed-test-data 
                            {--chamber= : 指定方舱ID，默认为第一个方舱}
                            {--logs=10 : 生成日志数量}
                            {--schedules : 生成时段配置}';

    protected $description = '为自动控制功能生成测试数据';

    public function handle()
    {
        $this->info('开始生成自动控制测试数据...');

        // 获取方舱
        $chamberId = $this->option('chamber');
        if (! $chamberId) {
            $chamber = Chamber::first();
            if (! $chamber) {
                $this->error('没有找到方舱，请先创建方舱');

                return 1;
            }
            $chamberId = $chamber->id;
        } else {
            $chamber = Chamber::find($chamberId);
            if (! $chamber) {
                $this->error("方舱 ID {$chamberId} 不存在");

                return 1;
            }
        }

        $this->info("正在为方舱 [{$chamber->name}] 生成测试数据...");

        // 1. 生成配置数据
        $this->generateConfigs($chamber);

        // 2. 生成状态数据
        $this->generateStates($chamber);

        // 3. 生成日志数据
        if ($this->option('logs') > 0) {
            $this->generateLogs($chamber, (int) $this->option('logs'));
        }

        // 4. 生成时段配置
        if ($this->option('schedules')) {
            $this->generateSchedules($chamber);
        }

        $this->newLine();
        $this->info('✅ 测试数据生成完成！');
        $this->newLine();
        $this->info('生成的测试数据：');
        $this->info("- 方舱: {$chamber->name} (ID: {$chamber->id})");
        $this->info('- 控制配置: 5个类型');
        $this->info("- 控制日志: {$this->option('logs')} 条");
        if ($this->option('schedules')) {
            $this->info('- 时段配置: 已生成');
        }

        return 0;
    }

    protected function generateConfigs($chamber)
    {
        $this->info('生成控制配置...');

        $configs = [
            'temperature' => [
                'mode' => 'auto_threshold',
                'is_enabled' => true,
                'threshold_upper' => 28.0,
                'threshold_lower' => 22.0,
                'threshold_sensor' => 'temperature',
                'cycle_run_duration' => 15,
                'cycle_run_unit' => 'minutes',
                'cycle_stop_duration' => 45,
                'cycle_stop_unit' => 'minutes',
            ],
            'humidity' => [
                'mode' => 'auto_cycle',
                'is_enabled' => true,
                'threshold_upper' => 85.0,
                'threshold_lower' => 70.0,
                'threshold_sensor' => 'humidity',
                'cycle_run_duration' => 10,
                'cycle_run_unit' => 'minutes',
                'cycle_stop_duration' => 20,
                'cycle_stop_unit' => 'minutes',
            ],
            'fresh_air' => [
                'mode' => 'auto_schedule',
                'is_enabled' => true,
                'threshold_upper' => 1500,
                'threshold_lower' => null,
                'threshold_sensor' => 'co2_level',
                'cycle_run_duration' => 30,
                'cycle_run_unit' => 'minutes',
                'cycle_stop_duration' => 30,
                'cycle_stop_unit' => 'minutes',
            ],
            'exhaust' => [
                'mode' => 'auto_cycle',
                'is_enabled' => false,
                'threshold_upper' => null,
                'threshold_lower' => null,
                'threshold_sensor' => null,
                'cycle_run_duration' => 5,
                'cycle_run_unit' => 'minutes',
                'cycle_stop_duration' => 55,
                'cycle_stop_unit' => 'minutes',
            ],
            'lighting' => [
                'mode' => 'auto_schedule',
                'is_enabled' => true,
                'threshold_upper' => null,
                'threshold_lower' => null,
                'threshold_sensor' => null,
                'cycle_run_duration' => 60,
                'cycle_run_unit' => 'minutes',
                'cycle_stop_duration' => 0,
                'cycle_stop_unit' => 'minutes',
            ],
        ];

        foreach ($configs as $controlType => $configData) {
            ChamberControlConfig::updateOrCreate(
                [
                    'chamber_id' => $chamber->id,
                    'control_type' => $controlType,
                ],
                array_merge($configData, [
                    'linkage_config' => json_encode([
                        'link_inner_circulation' => true,
                        'link_exhaust' => false,
                        'link_fresh_air' => $controlType === 'fresh_air',
                    ]),
                    'delay_seconds' => 0,
                ])
            );
        }

        $this->info('  ✓ 控制配置已生成');
    }

    protected function generateStates($chamber)
    {
        $this->info('生成设备状态...');

        $states = [
            'temperature' => [
                'current_state' => true,
                'current_mode' => 'auto',
                'is_manual_override' => false,
            ],
            'humidity' => [
                'current_state' => false,
                'current_mode' => 'auto',
                'is_manual_override' => false,
            ],
            'fresh_air' => [
                'current_state' => true,
                'current_mode' => 'auto',
                'is_manual_override' => false,
            ],
            'exhaust' => [
                'current_state' => false,
                'current_mode' => 'off',
                'is_manual_override' => false,
            ],
            'lighting' => [
                'current_state' => true,
                'current_mode' => 'auto',
                'is_manual_override' => false,
            ],
        ];

        foreach ($states as $controlType => $stateData) {
            ChamberControlState::updateOrCreate(
                [
                    'chamber_id' => $chamber->id,
                    'control_type' => $controlType,
                ],
                array_merge($stateData, [
                    'last_switch_at' => now()->subMinutes(rand(1, 60)),
                    'next_switch_at' => now()->addMinutes(rand(1, 30)),
                    'override_until' => null,
                ])
            );
        }

        $this->info('  ✓ 设备状态已生成');
    }

    protected function generateLogs($chamber, $count)
    {
        $this->info("生成 {$count} 条控制日志...");

        $controlTypes = ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting'];
        $triggerTypes = ['auto', 'manual', 'linkage'];
        $actions = ['turn_on', 'turn_off'];
        $users = User::pluck('id')->toArray();
        if (empty($users)) {
            $users = [1];
        }

        $logs = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            $controlType = $controlTypes[array_rand($controlTypes)];
            $triggerType = $triggerTypes[array_rand($triggerTypes)];
            $action = $actions[array_rand($actions)];
            $executedAt = $now->copy()->subMinutes(rand(0, 1440)); // 过去24小时内

            $logs[] = [
                'chamber_id' => $chamber->id,
                'control_type' => $controlType,
                'action' => $action,
                'trigger_type' => $triggerType,
                'trigger_reason' => $this->generateReason($controlType, $triggerType, $action),
                'executed_by' => $triggerType === 'manual' ? $users[array_rand($users)] : null,
                'executed_at' => $executedAt,
                'created_at' => $executedAt,
                'updated_at' => $executedAt,
            ];
        }

        // 按时间排序并插入
        usort($logs, function ($a, $b) {
            return strcmp($b['executed_at'], $a['executed_at']);
        });

        ChamberControlLog::insert($logs);

        $this->info("  ✓ {$count} 条日志已生成");
    }

    protected function generateSchedules($chamber)
    {
        $this->info('生成时段配置...');

        // 温度时段配置
        $tempSchedules = [
            [
                'schedule_index' => 1,
                'is_enabled' => true,
                'start_time' => '06:00',
                'end_time' => '12:00',
                'temp_cooling_upper' => 26,
                'temp_cooling_lower' => 24,
                'temp_heating_upper' => 24,
                'temp_heating_lower' => 22,
            ],
            [
                'schedule_index' => 2,
                'is_enabled' => true,
                'start_time' => '12:00',
                'end_time' => '18:00',
                'temp_cooling_upper' => 28,
                'temp_cooling_lower' => 26,
                'temp_heating_upper' => 26,
                'temp_heating_lower' => 24,
            ],
            [
                'schedule_index' => 3,
                'is_enabled' => true,
                'start_time' => '18:00',
                'end_time' => '22:00',
                'temp_cooling_upper' => 25,
                'temp_cooling_lower' => 23,
                'temp_heating_upper' => 23,
                'temp_heating_lower' => 21,
            ],
            [
                'schedule_index' => 4,
                'is_enabled' => true,
                'start_time' => '22:00',
                'end_time' => '06:00',
                'temp_cooling_upper' => 24,
                'temp_cooling_lower' => 22,
                'temp_heating_upper' => 22,
                'temp_heating_lower' => 20,
            ],
        ];

        foreach ($tempSchedules as $schedule) {
            ChamberSchedule::updateOrCreate(
                [
                    'chamber_id' => $chamber->id,
                    'control_type' => 'temperature',
                    'schedule_index' => $schedule['schedule_index'],
                ],
                $schedule
            );
        }

        // 光照时段配置
        $lightSchedules = [
            [
                'schedule_index' => 1,
                'is_enabled' => true,
                'start_time' => '06:00',
                'end_time' => '12:00',
            ],
            [
                'schedule_index' => 2,
                'is_enabled' => false,
                'start_time' => '12:00',
                'end_time' => '14:00',
            ],
            [
                'schedule_index' => 3,
                'is_enabled' => true,
                'start_time' => '14:00',
                'end_time' => '18:00',
            ],
        ];

        foreach ($lightSchedules as $schedule) {
            ChamberSchedule::updateOrCreate(
                [
                    'chamber_id' => $chamber->id,
                    'control_type' => 'lighting',
                    'schedule_index' => $schedule['schedule_index'],
                ],
                $schedule
            );
        }

        $this->info('  ✓ 时段配置已生成');
    }

    protected function generateReason($controlType, $triggerType, $action)
    {
        $typeNames = [
            'temperature' => '温度控制',
            'humidity' => '加湿控制',
            'fresh_air' => '新风控制',
            'exhaust' => '排风控制',
            'lighting' => '光照控制',
        ];

        $actionText = $action === 'turn_on' ? '开启' : '关闭';

        switch ($triggerType) {
            case 'auto':
                return "{$typeNames[$controlType]} 自动{$actionText}";
            case 'manual':
                return "{$typeNames[$controlType]} 手动{$actionText}";
            case 'linkage':
                return "{$typeNames[$controlType]} 联动{$actionText}";
            default:
                return "{$typeNames[$controlType]} {$actionText}";
        }
    }
}
