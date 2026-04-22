<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chamber;
use App\Models\ChamberControlConfig;
use App\Models\ChamberControlState;
use App\Models\ChamberManualControl;
use App\Models\ChamberSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChamberControlApiController extends Controller
{
    /**
     * 控制类型映射
     */
    private const CONTROL_TYPE_MAP = [
        'temperature' => 'temperature',
        'humidification' => 'humidity',
        'fresh_air' => 'fresh_air',
        'exhaust' => 'exhaust',
        'lighting' => 'lighting',
    ];

    /**
     * 检查用户权限
     */
    protected function checkPermission(string $permission): ?JsonResponse
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user();

        // 超级管理员跳过权限检查
        if ($user->email === 'admin@mushroom.com') {
            return null;
        }

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'error' => '没有权限执行此操作',
                'permission' => $permission,
            ], 403);
        }

        return null;
    }

    /**
     * 获取方舱自动控制配置
     */
    public function show(Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.view')) {
            return $response;
        }

        $configs = [];
        foreach (self::CONTROL_TYPE_MAP as $key => $dbType) {
            $config = ChamberControlConfig::where('chamber_id', $chamber->id)
                ->where('control_type', $dbType)
                ->first();

            if (! $config) {
                // 创建默认配置
                $config = ChamberControlConfig::create([
                    'chamber_id' => $chamber->id,
                    'control_type' => $dbType,
                    'mode' => $key === 'temperature' ? 'auto_schedule' : 'off',
                    'is_enabled' => false,
                ]);
            }

            $state = ChamberControlState::where('chamber_id', $chamber->id)
                ->where('control_type', $dbType)
                ->first();

            $configs[$key] = [
                'mode' => $config->mode,
                'is_enabled' => $config->is_enabled,
                'threshold_upper' => $config->threshold_upper,
                'threshold_lower' => $config->threshold_lower,
                'cycle_run_duration' => $config->cycle_run_duration,
                'cycle_run_unit' => $config->cycle_run_unit,
                'cycle_stop_duration' => $config->cycle_stop_duration,
                'cycle_stop_unit' => $config->cycle_stop_unit,
                'linkage_config' => $config->linkage_config,
                'delay_cooling_heating' => $config->delay_cooling_heating,
                'delay_stop_cycle' => $config->delay_stop_cycle,
                'inner_cycle_run' => $config->inner_cycle_run,
                'inner_cycle_stop' => $config->inner_cycle_stop,
                'current_state' => $state?->current_state ?? false,
                'current_mode' => $state?->current_mode ?? 'off',
                'schedules_count' => $config->schedules()->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'chamber_id' => $chamber->id,
                'chamber_name' => $chamber->name,
                'chamber_code' => $chamber->code,
                'configs' => $configs,
            ],
        ]);
    }

    /**
     * 更新自动控制配置
     */
    public function update(Request $request, Chamber $chamber, string $controlType): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.edit')) {
            return $response;
        }

        if (! isset(self::CONTROL_TYPE_MAP[$controlType])) {
            return response()->json([
                'success' => false,
                'error' => '无效的控制类型',
            ], 400);
        }

        $dbType = self::CONTROL_TYPE_MAP[$controlType];

        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:off,manual,auto_cycle,auto_threshold,auto_schedule',
            'is_enabled' => 'required|boolean',
            'threshold_upper' => 'nullable|numeric',
            'threshold_lower' => 'nullable|numeric',
            'cycle_run_duration' => 'nullable|integer',
            'cycle_run_unit' => 'nullable|in:seconds,minutes,hours',
            'cycle_stop_duration' => 'nullable|integer',
            'cycle_stop_unit' => 'nullable|in:seconds,minutes,hours',
            'linkage_config' => 'nullable|array',
            'delay_cooling_heating' => 'nullable|integer',
            'delay_stop_cycle' => 'nullable|integer',
            'inner_cycle_run' => 'nullable|integer',
            'inner_cycle_stop' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $config = ChamberControlConfig::updateOrCreate(
            [
                'chamber_id' => $chamber->id,
                'control_type' => $dbType,
            ],
            $validator->validated()
        );

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
            'success' => true,
            'message' => '配置已更新'.($devDevice && $devDevice->code ? '，正在同步到边缘设备' : ''),
            'data' => $config,
        ]);
    }

    /**
     * 手动控制设备开关
     */
    public function manualControl(Request $request, Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.manual_control.view')) {
            return $response;
        }

        $deviceFields = [
            'inner_circulation',
            'cooling',
            'heating',
            'fan',
            'four_way_valve',
            'fresh_air',
            'humidification',
            'lighting_supplement',
            'lighting',
        ];

        $rules = [];
        foreach ($deviceFields as $field) {
            $rules[$field] = 'sometimes|boolean';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 获取该方舱最新一条监控记录
        $record = ChamberManualControl::where('chamber_id', $chamber->id)
            ->latest('recorded_at')
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'error' => '未找到该方舱的监控记录',
            ], 404);
        }

        // 只更新传入的设备字段
        $updateData = [];
        foreach ($deviceFields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->boolean($field);
            }
        }

        if (empty($updateData)) {
            return response()->json([
                'success' => false,
                'error' => '没有提供需要更新的设备状态',
            ], 422);
        }

        // 查找关联的边缘设备
        $devDevice = $chamber->devices()->first();
        if (! $devDevice || ! $devDevice->code) {
            return response()->json([
                'success' => false,
                'message' => '未找到该方舱的边缘设备',
            ], 404);
        }

        try {
            // 使用队列异步发送 MQTT 命令（方案 A：先发 MQTT，成功后更新数据库）
            \App\Jobs\SendMqttControlCommand::dispatch(
                chamberId: $chamber->id,
                deviceCode: $devDevice->code,
                actions: $updateData,
                overrideMinutes: $request->input('override_minutes'),
                userId: auth()->id(),
            );

            return response()->json([
                'success' => true,
                'message' => '控制命令已下发到边缘设备，将在队列中异步执行',
                'data' => [
                    'chamber_id' => $chamber->id,
                    'device_code' => $devDevice->code,
                    'updated_fields' => $updateData,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('MQTT publish failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '命令下发失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取设备实时状态
     */
    public function status(Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.view')) {
            return $response;
        }

        $devices = [];
        foreach (self::CONTROL_TYPE_MAP as $key => $dbType) {
            $state = ChamberControlState::where('chamber_id', $chamber->id)
                ->where('control_type', $dbType)
                ->first();

            $devices[$key] = [
                'current_state' => $state?->current_state ?? false,
                'current_mode' => $state?->current_mode ?? 'off',
                'last_switch_at' => $state?->last_switch_at,
                'is_manual_override' => $state?->is_manual_override ?? false,
                'override_until' => $state?->override_until,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'chamber_id' => $chamber->id,
                'chamber_name' => $chamber->name,
                'chamber_code' => $chamber->code,
                'devices' => $devices,
            ],
        ]);
    }

    /**
     * 获取控制日志
     */
    public function logs(Request $request, Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.control_log.view')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'control_type' => 'nullable|in:temperature,humidification,fresh_air,exhaust,lighting',
            'trigger_type' => 'nullable|in:manual,auto,schedule,threshold,cycle,linkage',
            'date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = \App\Models\ChamberControlLog::where('chamber_id', $chamber->id);

        if ($request->has('control_type')) {
            $dbType = self::CONTROL_TYPE_MAP[$request->control_type] ?? $request->control_type;
            $query->where('control_type', $dbType);
        }

        if ($request->has('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $perPage = $request->input('per_page', 20);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * 获取时段配置
     */
    public function schedules(Request $request, Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.view')) {
            return $response;
        }

        $query = ChamberSchedule::where('chamber_id', $chamber->id);

        if ($request->has('control_type')) {
            $controlType = $request->query('control_type');
            if (! isset(self::CONTROL_TYPE_MAP[$controlType])) {
                return response()->json([
                    'success' => false,
                    'error' => '无效的控制类型',
                ], 400);
            }
            $query->where('control_type', self::CONTROL_TYPE_MAP[$controlType]);
        }

        $schedules = $query->orderBy('schedule_index')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'chamber_id' => $chamber->id,
                'control_type' => $request->query('control_type'),
                'schedules' => $schedules,
            ],
        ]);
    }

    /**
     * 更新时段配置
     */
    public function updateSchedule(Request $request, Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.auto_control.edit')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'control_type' => 'required|in:temperature,humidification,fresh_air,exhaust,lighting',
            'schedules' => 'required|array|min:1',
            'schedules.*.schedule_index' => 'required|integer',
            'schedules.*.is_enabled' => 'required|boolean',
            'schedules.*.start_time' => 'required|date_format:H:i:s',
            'schedules.*.end_time' => 'required|date_format:H:i:s',
            'schedules.*.temp_cooling_upper' => 'nullable|numeric',
            'schedules.*.temp_cooling_lower' => 'nullable|numeric',
            'schedules.*.temp_heating_upper' => 'nullable|numeric',
            'schedules.*.temp_heating_lower' => 'nullable|numeric',
            'schedules.*.humidity_upper' => 'nullable|numeric',
            'schedules.*.humidity_lower' => 'nullable|numeric',
            'schedules.*.co2_upper' => 'nullable|integer',
            'schedules.*.co2_lower' => 'nullable|integer',
            'schedules.*.cycle_run_minutes' => 'nullable|integer',
            'schedules.*.cycle_stop_minutes' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $controlType = $request->input('control_type');
        $dbType = self::CONTROL_TYPE_MAP[$controlType];

        // 删除该方舱该控制类型下所有现有 schedules
        ChamberSchedule::where('chamber_id', $chamber->id)
            ->where('control_type', $dbType)
            ->delete();

        // 批量插入新 schedules
        $createdSchedules = [];
        foreach ($request->input('schedules') as $scheduleData) {
            $schedule = ChamberSchedule::create([
                'chamber_id' => $chamber->id,
                'control_type' => $dbType,
                'schedule_index' => $scheduleData['schedule_index'],
                'is_enabled' => $scheduleData['is_enabled'],
                'start_time' => $scheduleData['start_time'],
                'end_time' => $scheduleData['end_time'],
                'temp_cooling_upper' => $scheduleData['temp_cooling_upper'] ?? null,
                'temp_cooling_lower' => $scheduleData['temp_cooling_lower'] ?? null,
                'temp_heating_upper' => $scheduleData['temp_heating_upper'] ?? null,
                'temp_heating_lower' => $scheduleData['temp_heating_lower'] ?? null,
                'humidity_upper' => $scheduleData['humidity_upper'] ?? null,
                'humidity_lower' => $scheduleData['humidity_lower'] ?? null,
                'co2_upper' => $scheduleData['co2_upper'] ?? null,
                'co2_lower' => $scheduleData['co2_lower'] ?? null,
                'cycle_run_minutes' => $scheduleData['cycle_run_minutes'] ?? null,
                'cycle_stop_minutes' => $scheduleData['cycle_stop_minutes'] ?? null,
            ]);
            $createdSchedules[] = $schedule;
        }

        return response()->json([
            'success' => true,
            'message' => '时段配置已更新',
            'data' => $createdSchedules,
        ]);
    }
}
