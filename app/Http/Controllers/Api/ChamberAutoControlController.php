<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chamber;
use App\Models\ChamberControlConfig;
use App\Models\ChamberControlLog;
use App\Models\ChamberControlState;
use App\Models\ChamberSchedule;
use App\Services\ChamberAutoControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChamberAutoControlController extends Controller
{
    /**
     * API控制类型到数据库控制类型的映射
     */
    protected const CONTROL_TYPE_MAP = [
        'temperature' => 'temperature',
        'humidification' => 'humidity',
        'fresh_air' => 'fresh_air',
        'exhaust' => 'exhaust',
        'lighting' => 'lighting',
    ];

    /**
     * 数据库控制类型到API控制类型的映射
     */
    protected const REVERSE_CONTROL_TYPE_MAP = [
        'temperature' => 'temperature',
        'humidity' => 'humidification',
        'fresh_air' => 'fresh_air',
        'exhaust' => 'exhaust',
        'lighting' => 'lighting',
    ];

    /**
     * 将API控制类型转换为数据库控制类型
     */
    protected function toDbControlType(string $apiType): string
    {
        return self::CONTROL_TYPE_MAP[$apiType] ?? $apiType;
    }

    /**
     * 将数据库控制类型转换为API控制类型
     */
    protected function toApiControlType(string $dbType): string
    {
        return self::REVERSE_CONTROL_TYPE_MAP[$dbType] ?? $dbType;
    }

    /**
     * 检查用户权限
     */
    protected function checkPermission(string $permission): ?JsonResponse
    {
        // 如果用户未登录，允许访问（开发调试模式）
        if (! auth()->check()) {
            // 生产环境应该返回：return response()->json(['error' => '请先登录'], 401);
            return null;
        }

        $user = auth()->user();

        // 超级管理员跳过权限检查
        if ($user->email === 'admin@mushroom.com') {
            return null;
        }

        // 检查具体权限
        if (! $user->hasPermission($permission)) {
            return response()->json([
                'error' => '没有权限执行此操作',
                'permission' => $permission,
                'user' => $user->email,
            ], 403);
        }

        return null;
    }

    /**
     * 获取方舱自动控制配置
     */
    public function getConfig(int $chamberId): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.view')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        // 定义所有API控制类型（前端使用的名称）
        $apiControlTypes = ['temperature', 'humidification', 'fresh_air', 'exhaust', 'lighting'];

        // 确保所有控制类型都有配置（如果不存在则创建默认配置）
        foreach ($apiControlTypes as $apiType) {
            $dbType = $this->toDbControlType($apiType);
            ChamberControlConfig::getOrCreate($chamber->id, $dbType);
            ChamberControlState::getOrCreate($chamber->id, $dbType);
        }

        $configs = ChamberControlConfig::where('chamber_id', $chamber->id)->get();
        $states = ChamberControlState::where('chamber_id', $chamber->id)->get();

        $result = [];
        foreach ($configs as $config) {
            $state = $states->firstWhere('control_type', $config->control_type);
            $apiType = $this->toApiControlType($config->control_type);

            // 转换模式名称为前端格式
            $modeMap = [
                'auto_schedule' => 'schedule',
                'auto_threshold' => 'threshold',
                'auto_cycle' => 'cycle',
                'off' => 'off',
                'manual' => 'manual',
            ];

            $configData = [
                'mode' => $modeMap[$config->mode] ?? $config->mode,
                'is_enabled' => $config->is_enabled,
                'current_state' => $state?->current_state ?? false,
                'current_mode' => $state?->current_mode ?? 'off',
                'is_manual_override' => $state?->is_manual_override ?? false,
                'threshold_upper' => $config->threshold_upper,
                'threshold_lower' => $config->threshold_lower,
                'cycle_run_duration' => $config->cycle_run_duration,
                'cycle_run_unit' => $config->cycle_run_unit,
                'cycle_stop_duration' => $config->cycle_stop_duration,
                'cycle_stop_unit' => $config->cycle_stop_unit,
                'linkage_config' => $config->linkage_config ?: [
                    'link_inner_circulation' => true,
                    'link_exhaust' => false,
                    'link_fresh_air' => false,
                ],
                'delay_cooling_heating' => $config->delay_cooling_heating ?? 0,
                'delay_stop_cycle' => $config->delay_stop_cycle ?? 0,
                'inner_cycle_run' => $config->inner_cycle_run ?? 0,
                'inner_cycle_stop' => $config->inner_cycle_stop ?? 0,
            ];

            // 温度控制添加时段配置
            if ($apiType === 'temperature') {
                $schedules = ChamberSchedule::where('chamber_id', $chamber->id)
                    ->where('control_type', $config->control_type)
                    ->orderBy('schedule_index')
                    ->get()
                    ->map(function ($schedule) {
                        return [
                            'start_time' => is_string($schedule->start_time) ? substr($schedule->start_time, 0, 5) : $schedule->start_time->format('H:i'),
                            'end_time' => is_string($schedule->end_time) ? substr($schedule->end_time, 0, 5) : $schedule->end_time->format('H:i'),
                            'cooling_upper' => $schedule->temp_cooling_upper,
                            'cooling_lower' => $schedule->temp_cooling_lower,
                            'heating_upper' => $schedule->temp_heating_upper,
                            'heating_lower' => $schedule->temp_heating_lower,
                            'is_enabled' => $schedule->is_enabled,
                        ];
                    });

                // 如果没有时段配置，返回默认的一个时段
                if ($schedules->isEmpty()) {
                    $configData['schedules'] = [
                        [
                            'start_time' => '06:00',
                            'end_time' => '22:00',
                            'cooling_upper' => 28.0,
                            'cooling_lower' => 26.0,
                            'heating_upper' => 18.0,
                            'heating_lower' => 16.0,
                            'is_enabled' => true,
                        ],
                    ];
                } else {
                    $configData['schedules'] = $schedules->toArray();
                }
            }

            // 加湿控制添加时段配置
            if ($apiType === 'humidification') {
                $schedules = ChamberSchedule::where('chamber_id', $chamber->id)
                    ->where('control_type', $config->control_type)
                    ->orderBy('schedule_index')
                    ->get()
                    ->map(function ($schedule) {
                        return [
                            'start_time' => is_string($schedule->start_time) ? substr($schedule->start_time, 0, 5) : $schedule->start_time->format('H:i'),
                            'end_time' => is_string($schedule->end_time) ? substr($schedule->end_time, 0, 5) : $schedule->end_time->format('H:i'),
                            'humidity_upper' => $schedule->humidity_upper,
                            'humidity_lower' => $schedule->humidity_lower,
                            'is_enabled' => $schedule->is_enabled,
                        ];
                    });

                // 如果没有时段配置，返回默认的一个时段
                if ($schedules->isEmpty()) {
                    $configData['schedules'] = [
                        [
                            'start_time' => '06:00',
                            'end_time' => '22:00',
                            'humidity_upper' => 85.0,
                            'humidity_lower' => 70.0,
                            'is_enabled' => true,
                        ],
                    ];
                } else {
                    $configData['schedules'] = $schedules->toArray();
                }
            }

            // 新风控制添加时段配置
            if ($apiType === 'fresh_air') {
                $schedules = ChamberSchedule::where('chamber_id', $chamber->id)
                    ->where('control_type', $config->control_type)
                    ->orderBy('schedule_index')
                    ->get()
                    ->map(function ($schedule) {
                        return [
                            'start_time' => is_string($schedule->start_time) ? substr($schedule->start_time, 0, 5) : $schedule->start_time->format('H:i'),
                            'end_time' => is_string($schedule->end_time) ? substr($schedule->end_time, 0, 5) : $schedule->end_time->format('H:i'),
                            'co2_upper' => $schedule->co2_upper,
                            'co2_lower' => $schedule->co2_lower,
                            'is_enabled' => $schedule->is_enabled,
                        ];
                    });

                // 如果没有时段配置，返回默认的一个时段
                if ($schedules->isEmpty()) {
                    $configData['schedules'] = [
                        [
                            'start_time' => '06:00',
                            'end_time' => '22:00',
                            'co2_upper' => 1500.0,
                            'co2_lower' => 800.0,
                            'is_enabled' => true,
                        ],
                    ];
                } else {
                    $configData['schedules'] = $schedules->toArray();
                }
            }

            // 排风控制添加时段配置
            if ($apiType === 'exhaust') {
                $schedules = ChamberSchedule::where('chamber_id', $chamber->id)
                    ->where('control_type', $config->control_type)
                    ->orderBy('schedule_index')
                    ->get()
                    ->map(function ($schedule) {
                        return [
                            'start_time' => is_string($schedule->start_time) ? substr($schedule->start_time, 0, 5) : $schedule->start_time->format('H:i'),
                            'end_time' => is_string($schedule->end_time) ? substr($schedule->end_time, 0, 5) : $schedule->end_time->format('H:i'),
                            'co2_upper' => $schedule->co2_upper,
                            'co2_lower' => $schedule->co2_lower,
                            'is_enabled' => $schedule->is_enabled,
                        ];
                    });

                // 如果没有时段配置，返回默认的一个时段
                if ($schedules->isEmpty()) {
                    $configData['schedules'] = [
                        [
                            'start_time' => '06:00',
                            'end_time' => '22:00',
                            'co2_upper' => 1500.0,
                            'co2_lower' => 800.0,
                            'is_enabled' => true,
                        ],
                    ];
                } else {
                    $configData['schedules'] = $schedules->toArray();
                }
            }

            // 光照控制添加时段配置
            if ($apiType === 'lighting') {
                $schedules = ChamberSchedule::where('chamber_id', $chamber->id)
                    ->where('control_type', $config->control_type)
                    ->orderBy('schedule_index')
                    ->get()
                    ->map(function ($schedule) {
                        // 智能转换：如果值能被60整除且>=60，使用小时，否则使用分钟
                        $cycleRunMinutes = $schedule->cycle_run_minutes ?? 0;
                        $cycleStopMinutes = $schedule->cycle_stop_minutes ?? 0;

                        // LED开启时长和单位
                        if ($cycleRunMinutes >= 60 && $cycleRunMinutes % 60 === 0) {
                            $ledOnDuration = $cycleRunMinutes / 60;
                            $ledOnUnit = 'hours';
                        } else {
                            $ledOnDuration = $cycleRunMinutes;
                            $ledOnUnit = 'minutes';
                        }

                        // LED关闭时长和单位
                        if ($cycleStopMinutes >= 60 && $cycleStopMinutes % 60 === 0) {
                            $ledOffDuration = $cycleStopMinutes / 60;
                            $ledOffUnit = 'hours';
                        } else {
                            $ledOffDuration = $cycleStopMinutes;
                            $ledOffUnit = 'minutes';
                        }

                        return [
                            'start_time' => is_string($schedule->start_time) ? substr($schedule->start_time, 0, 5) : $schedule->start_time->format('H:i'),
                            'end_time' => is_string($schedule->end_time) ? substr($schedule->end_time, 0, 5) : $schedule->end_time->format('H:i'),
                            'led_on_duration' => $ledOnDuration,
                            'led_on_unit' => $ledOnUnit,
                            'led_off_duration' => $ledOffDuration,
                            'led_off_unit' => $ledOffUnit,
                            'is_enabled' => $schedule->is_enabled,
                        ];
                    });

                // 如果没有时段配置，返回默认的一个时段
                if ($schedules->isEmpty()) {
                    $configData['schedules'] = [
                        [
                            'start_time' => '06:00',
                            'end_time' => '22:00',
                            'led_on_duration' => 30,
                            'led_on_unit' => 'minutes',
                            'led_off_duration' => 30,
                            'led_off_unit' => 'minutes',
                            'is_enabled' => true,
                        ],
                    ];
                } else {
                    $configData['schedules'] = $schedules->toArray();
                }
            }

            $result[$apiType] = $configData;
        }

        return response()->json([
            'chamber_id' => $chamber->id,
            'chamber_name' => $chamber->name,
            'configs' => $result,
        ]);
    }

    /**
     * 更新自动控制配置
     */
    public function updateConfig(Request $request, int $chamberId, string $controlType): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.edit')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        $dbControlType = $this->toDbControlType($controlType);
        $config = ChamberControlConfig::getOrCreate($chamber->id, $dbControlType);

        try {
            $validated = $request->validate([
                'mode' => 'required|in:auto_schedule,threshold,cycle,off,manual,schedule',
                'is_enabled' => 'boolean',
                'threshold_upper' => 'nullable|numeric',
                'threshold_lower' => 'nullable|numeric',
                'cycle_run_duration' => 'nullable|integer',
                'cycle_run_unit' => 'nullable|in:seconds,minutes,hours',
                'cycle_stop_duration' => 'nullable|integer',
                'cycle_stop_unit' => 'nullable|in:seconds,minutes,hours',
                'linkage_config' => 'nullable',
                'delay_cooling_heating' => 'nullable|integer|min:0',
                'delay_stop_cycle' => 'nullable|integer|min:0',
                'inner_cycle_run' => 'nullable|integer|min:0',
                'inner_cycle_stop' => 'nullable|integer|min:0',
                'schedules' => 'nullable|array',
                'schedules.*.start_time' => 'required_with:schedules|date_format:H:i',
                'schedules.*.end_time' => 'required_with:schedules|date_format:H:i',
                'schedules.*.cooling_upper' => 'nullable|numeric',
                'schedules.*.cooling_lower' => 'nullable|numeric',
                'schedules.*.heating_upper' => 'nullable|numeric',
                'schedules.*.heating_lower' => 'nullable|numeric',
                'schedules.*.humidity_upper' => 'nullable|numeric',
                'schedules.*.humidity_lower' => 'nullable|numeric',
                'schedules.*.co2_upper' => 'nullable|integer',
                'schedules.*.co2_lower' => 'nullable|integer',
                'schedules.*.led_on_duration' => 'nullable|integer',
                'schedules.*.led_on_unit' => 'nullable|in:minutes,hours',
                'schedules.*.led_off_duration' => 'nullable|integer',
                'schedules.*.led_off_unit' => 'nullable|in:minutes,hours',
                'schedules.*.is_enabled' => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => '验证失败',
                'messages' => $e->errors(),
            ], 422);
        }

        // 转换模式名称以适配数据库枚举
        $modeMap = [
            'auto_schedule' => 'auto_schedule',
            'threshold' => 'auto_threshold',
            'cycle' => 'auto_cycle',
            'off' => 'off',
            'manual' => 'manual',
            'schedule' => 'auto_schedule',
        ];
        $validated['mode'] = $modeMap[$validated['mode']] ?? $validated['mode'];

        // 提取并移除 schedules 从 validated 数据
        $schedules = $validated['schedules'] ?? null;
        unset($validated['schedules']);

        // 处理 linkage_config - 确保它是数组格式
        if (isset($validated['linkage_config'])) {
            // 如果是字符串，尝试解码
            if (is_string($validated['linkage_config'])) {
                $validated['linkage_config'] = json_decode($validated['linkage_config'], true) ?: [
                    'link_inner_circulation' => true,
                    'link_exhaust' => false,
                    'link_fresh_air' => false,
                ];
            }
            // 如果前端发送了扁平的 link_inner_circulation 等字段，合并到 linkage_config
            if (isset($request->link_inner_circulation)) {
                $validated['linkage_config']['link_inner_circulation'] = (bool) $request->link_inner_circulation;
            }
        }

        $config->update($validated);

        // 保存温度时段配置
        if ($controlType === 'temperature' && $schedules !== null) {
            // 删除旧的时段配置
            ChamberSchedule::where('chamber_id', $chamber->id)
                ->where('control_type', $dbControlType)
                ->delete();

            // 创建新的时段配置
            foreach ($schedules as $index => $scheduleData) {
                ChamberSchedule::create([
                    'chamber_id' => $chamber->id,
                    'control_type' => $dbControlType,
                    'schedule_index' => $index,
                    'is_enabled' => $scheduleData['is_enabled'] ?? false,
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'temp_cooling_upper' => $scheduleData['cooling_upper'] ?? null,
                    'temp_cooling_lower' => $scheduleData['cooling_lower'] ?? null,
                    'temp_heating_upper' => $scheduleData['heating_upper'] ?? null,
                    'temp_heating_lower' => $scheduleData['heating_lower'] ?? null,
                ]);
            }
        }

        // 保存加湿时段配置
        if ($controlType === 'humidification' && $schedules !== null) {
            // 删除旧的时段配置
            ChamberSchedule::where('chamber_id', $chamber->id)
                ->where('control_type', $dbControlType)
                ->delete();

            // 创建新的时段配置
            foreach ($schedules as $index => $scheduleData) {
                ChamberSchedule::create([
                    'chamber_id' => $chamber->id,
                    'control_type' => $dbControlType,
                    'schedule_index' => $index,
                    'is_enabled' => $scheduleData['is_enabled'] ?? false,
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'humidity_upper' => $scheduleData['humidity_upper'] ?? null,
                    'humidity_lower' => $scheduleData['humidity_lower'] ?? null,
                ]);
            }
        }

        // 保存新风时段配置
        if ($controlType === 'fresh_air' && $schedules !== null) {
            // 删除旧的时段配置
            ChamberSchedule::where('chamber_id', $chamber->id)
                ->where('control_type', $dbControlType)
                ->delete();

            // 创建新的时段配置
            foreach ($schedules as $index => $scheduleData) {
                ChamberSchedule::create([
                    'chamber_id' => $chamber->id,
                    'control_type' => $dbControlType,
                    'schedule_index' => $index,
                    'is_enabled' => $scheduleData['is_enabled'] ?? false,
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'co2_upper' => $scheduleData['co2_upper'] ?? null,
                    'co2_lower' => $scheduleData['co2_lower'] ?? null,
                ]);
            }
        }

        // 保存排风时段配置
        if ($controlType === 'exhaust' && $schedules !== null) {
            // 删除旧的时段配置
            ChamberSchedule::where('chamber_id', $chamber->id)
                ->where('control_type', $dbControlType)
                ->delete();

            // 创建新的时段配置
            foreach ($schedules as $index => $scheduleData) {
                ChamberSchedule::create([
                    'chamber_id' => $chamber->id,
                    'control_type' => $dbControlType,
                    'schedule_index' => $index,
                    'is_enabled' => $scheduleData['is_enabled'] ?? false,
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'co2_upper' => $scheduleData['co2_upper'] ?? null,
                    'co2_lower' => $scheduleData['co2_lower'] ?? null,
                ]);
            }
        }

        // 保存光照时段配置
        if ($controlType === 'lighting' && $schedules !== null) {
            // 删除旧的时段配置
            ChamberSchedule::where('chamber_id', $chamber->id)
                ->where('control_type', $dbControlType)
                ->delete();

            // 创建新的时段配置
            foreach ($schedules as $index => $scheduleData) {
                // 根据单位转换时长为分钟
                $ledOnDuration = $scheduleData['led_on_duration'] ?? 0;
                $ledOnUnit = $scheduleData['led_on_unit'] ?? 'minutes';
                $ledOffDuration = $scheduleData['led_off_duration'] ?? 0;
                $ledOffUnit = $scheduleData['led_off_unit'] ?? 'minutes';

                // 转换为分钟存储
                $cycleRunMinutes = $ledOnUnit === 'hours' ? $ledOnDuration * 60 : $ledOnDuration;
                $cycleStopMinutes = $ledOffUnit === 'hours' ? $ledOffDuration * 60 : $ledOffDuration;

                ChamberSchedule::create([
                    'chamber_id' => $chamber->id,
                    'control_type' => $dbControlType,
                    'schedule_index' => $index,
                    'is_enabled' => $scheduleData['is_enabled'] ?? false,
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'cycle_run_minutes' => $cycleRunMinutes,
                    'cycle_stop_minutes' => $cycleStopMinutes,
                ]);
            }
        }

        // 查找边缘设备并异步发送配置同步
        $devDevice = $chamber->devices()->first();
        if ($devDevice && $devDevice->code) {
            \App\Jobs\SendMqttAutoConfig::dispatch(
                chamberId: $chamber->id,
                deviceCode: $devDevice->code,
                controlType: $controlType,
                config: $config->fresh()->toArray()
            );
        }

        return response()->json([
            'message' => '配置已更新'.($devDevice && $devDevice->code ? '，正在同步到边缘设备' : ''),
            'config' => $config,
        ]);
    }

    /**
     * 手动控制设备
     */
    public function manualControl(
        Request $request,
        int $chamberId,
        string $controlType,
        ChamberAutoControlService $service
    ): JsonResponse {
        if ($response = $this->checkPermission('chambers.manual_control.view')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        $validated = $request->validate([
            'action' => 'required|in:turn_on,turn_off',
            'override_minutes' => 'nullable|integer|min:1',
        ]);

        $dbControlType = $this->toDbControlType($controlType);
        $config = ChamberControlConfig::getOrCreate($chamber->id, $dbControlType);

        if ($config->mode === 'off') {
            return response()->json([
                'error' => '该设备已关闭，无法手动控制',
                'message' => '请先开启设备或切换到手动/自动模式',
            ], 400);
        }

        try {
            $service->manualControl(
                chamberId: $chamber->id,
                controlType: $dbControlType,
                turnOn: $validated['action'] === 'turn_on',
                userId: auth()->id(),
                overrideMinutes: $validated['override_minutes'] ?? null
            );

            return response()->json([
                'message' => '控制指令已执行',
                'action' => $validated['action'],
                'control_type' => $controlType,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取设备状态
     */
    public function getDeviceStatus(int $chamberId): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.view')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        $states = ChamberControlState::where('chamber_id', $chamber->id)->get();

        $result = [];
        foreach ($states as $state) {
            $result[$state->control_type] = [
                'current_state' => $state->current_state,
                'current_mode' => $state->current_mode,
                'last_switch_at' => $state->last_switch_at?->toIso8601String(),
                'is_manual_override' => $state->is_manual_override,
                'override_until' => $state->override_until?->toIso8601String(),
            ];
        }

        return response()->json([
            'chamber_id' => $chamber->id,
            'chamber_name' => $chamber->name,
            'devices' => $result,
        ]);
    }

    /**
     * 获取控制日志
     */
    public function getLogs(Request $request, int $chamberId): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.control_log.view')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        $logs = ChamberControlLog::where('chamber_id', $chamber->id)
            ->when($request->control_type, fn ($q, $type) => $q->where('control_type', $this->toDbControlType($type)))
            ->when($request->trigger_type, fn ($q, $type) => $q->where('trigger_type', $type))
            ->when($request->date, fn ($q, $date) => $q->whereDate('executed_at', $date))
            ->orderBy('executed_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($logs);
    }

    /**
     * 获取时段配置
     */
    public function getSchedules(int $chamberId, string $controlType): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.view')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        $dbControlType = $this->toDbControlType($controlType);
        $schedules = ChamberSchedule::where('chamber_id', $chamber->id)
            ->where('control_type', $dbControlType)
            ->orderBy('schedule_index')
            ->get();

        return response()->json([
            'chamber_id' => $chamber->id,
            'control_type' => $controlType,
            'schedules' => $schedules,
        ]);
    }

    /**
     * 更新时段配置
     */
    public function updateSchedule(
        Request $request,
        int $chamberId,
        string $controlType,
        int $scheduleIndex
    ): JsonResponse {
        if ($response = $this->checkPermission('chambers.auto_control.edit')) {
            return $response;
        }

        $chamber = Chamber::find($chamberId);

        if (! $chamber) {
            return response()->json(['error' => '方舱不存在'], 404);
        }

        $validated = $request->validate([
            'is_enabled' => 'boolean',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'temp_cooling_upper' => 'nullable|numeric',
            'temp_cooling_lower' => 'nullable|numeric',
            'temp_heating_upper' => 'nullable|numeric',
            'temp_heating_lower' => 'nullable|numeric',
            'humidity_upper' => 'nullable|numeric',
            'humidity_lower' => 'nullable|numeric',
            'co2_upper' => 'nullable|integer',
            'co2_lower' => 'nullable|integer',
            'cycle_run_minutes' => 'nullable|integer',
            'cycle_stop_minutes' => 'nullable|integer',
        ]);

        $dbControlType = $this->toDbControlType($controlType);
        $schedule = ChamberSchedule::updateOrCreate(
            [
                'chamber_id' => $chamber->id,
                'control_type' => $dbControlType,
                'schedule_index' => $scheduleIndex,
            ],
            $validated
        );

        return response()->json([
            'message' => '时段配置已更新',
            'schedule' => $schedule,
        ]);
    }
}
