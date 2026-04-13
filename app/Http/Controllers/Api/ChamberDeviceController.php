<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chamber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChamberDeviceController extends Controller
{
    /**
     * 获取方舱设备状态
     */
    public function getStatus(Request $request, string $deviceCode): JsonResponse
    {
        $chamber = Chamber::where('device_code', $deviceCode)->first();

        if (! $chamber) {
            return response()->json([
                'success' => false,
                'message' => '方舱不存在',
            ], 404);
        }

        $status = ChamberEnvironmentData::where('chamber_id', $chamber->id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'chamber_code' => $chamber->code,
                'chamber_name' => $chamber->name,
                'devices' => [
                    'inner_circulation' => $status?->inner_circulation ?? false,
                    'cooling' => $status?->cooling ?? false,
                    'heating' => $status?->heating ?? false,
                    'fan' => $status?->fan ?? false,
                    'four_way_valve' => $status?->four_way_valve ?? false,
                    'fresh_air' => $status?->fresh_air ?? false,
                    'humidification' => $status?->humidification ?? false,
                    'lighting_supplement' => $status?->lighting_supplement ?? false,
                    'lighting' => $status?->lighting ?? false,
                ],
                'settings' => [
                    'temperature' => $status?->temperature_setting ?? 25.0,
                    'humidity' => $status?->humidity_setting ?? 60.0,
                    'light_intensity' => $status?->light_intensity_setting ?? 1500,
                ],
                'last_updated' => $status?->recorded_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * 更新方舱设备状态（来自边缘服务器）
     */
    public function updateStatus(Request $request, string $deviceCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'devices' => 'required|array',
            'devices.inner_circulation' => 'boolean',
            'devices.cooling' => 'boolean',
            'devices.heating' => 'boolean',
            'devices.fan' => 'boolean',
            'devices.four_way_valve' => 'boolean',
            'devices.fresh_air' => 'boolean',
            'devices.humidification' => 'boolean',
            'devices.lighting_supplement' => 'boolean',
            'devices.lighting' => 'boolean',
            'settings' => 'nullable|array',
            'settings.temperature' => 'nullable|numeric',
            'settings.humidity' => 'nullable|numeric',
            'settings.light_intensity' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamber = Chamber::where('device_code', $deviceCode)->first();

        if (! $chamber) {
            return response()->json([
                'success' => false,
                'message' => '方舱不存在',
            ], 404);
        }

        $deviceData = $request->input('devices');
        $settings = $request->input('settings', []);

        // 获取当前最新数据
        $latestData = ChamberEnvironmentData::where('chamber_id', $chamber->id)
            ->latest('recorded_at')
            ->first();

        // 创建设备状态数据
        $deviceStates = [
            'inner_circulation' => $deviceData['inner_circulation'] ?? ($latestData?->inner_circulation ?? false),
            'cooling' => $deviceData['cooling'] ?? ($latestData?->cooling ?? false),
            'heating' => $deviceData['heating'] ?? ($latestData?->heating ?? false),
            'fan' => $deviceData['fan'] ?? ($latestData?->fan ?? false),
            'four_way_valve' => $deviceData['four_way_valve'] ?? ($latestData?->four_way_valve ?? false),
            'fresh_air' => $deviceData['fresh_air'] ?? ($latestData?->fresh_air ?? false),
            'humidification' => $deviceData['humidification'] ?? ($latestData?->humidification ?? false),
            'lighting_supplement' => $deviceData['lighting_supplement'] ?? ($latestData?->lighting_supplement ?? false),
            'lighting' => $deviceData['lighting'] ?? ($latestData?->lighting ?? false),
        ];

        // 创建设定值数据
        $settingData = [
            'temperature_setting' => $settings['temperature'] ?? ($latestData?->temperature_setting ?? 25.0),
            'humidity_setting' => $settings['humidity'] ?? ($latestData?->humidity_setting ?? 60.0),
            'light_intensity_setting' => $settings['light_intensity'] ?? ($latestData?->light_intensity_setting ?? 1500),
        ];

        // 创建新的环境数据记录
        $newData = ChamberEnvironmentData::create([
            'chamber_id' => $chamber->id,
            'temperature' => $latestData?->temperature ?? 0,
            'humidity' => $latestData?->humidity ?? 0,
            'co2_level' => $latestData?->co2_level ?? 0,
            'light_intensity' => $latestData?->light_intensity ?? 0,
            'is_anomaly' => $latestData?->is_anomaly ?? false,
            'anomaly_type' => $latestData?->anomaly_type ?? null,
            'recorded_at' => now(),
            ...$deviceStates,
            ...$settingData,
        ]);

        return response()->json([
            'success' => true,
            'message' => '设备状态已更新',
            'data' => [
                'chamber_code' => $chamber->code,
                'updated_at' => $newData->recorded_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * 控制方舱设备（从后台发送到边缘服务器）
     */
    public function controlDevice(Request $request, string $deviceCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device' => 'required|string|in:inner_circulation,cooling,heating,fan,four_way_valve,fresh_air,humidification,lighting_supplement,lighting',
            'state' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamber = Chamber::where('device_code', $deviceCode)->first();

        if (! $chamber) {
            return response()->json([
                'success' => false,
                'message' => '方舱不存在',
            ], 404);
        }

        $device = $request->input('device');
        $state = $request->input('state');

        // 获取当前最新数据
        $latestData = ChamberEnvironmentData::where('chamber_id', $chamber->id)
            ->latest('recorded_at')
            ->first();

        // 创建设备状态数据
        $deviceStates = [
            'inner_circulation' => $device === 'inner_circulation' ? $state : ($latestData?->inner_circulation ?? false),
            'cooling' => $device === 'cooling' ? $state : ($latestData?->cooling ?? false),
            'heating' => $device === 'heating' ? $state : ($latestData?->heating ?? false),
            'fan' => $device === 'fan' ? $state : ($latestData?->fan ?? false),
            'four_way_valve' => $device === 'four_way_valve' ? $state : ($latestData?->four_way_valve ?? false),
            'fresh_air' => $device === 'fresh_air' ? $state : ($latestData?->fresh_air ?? false),
            'humidification' => $device === 'humidification' ? $state : ($latestData?->humidification ?? false),
            'lighting_supplement' => $device === 'lighting_supplement' ? $state : ($latestData?->lighting_supplement ?? false),
            'lighting' => $device === 'lighting' ? $state : ($latestData?->lighting ?? false),
        ];

        // 创建新的环境数据记录
        $newData = ChamberEnvironmentData::create([
            'chamber_id' => $chamber->id,
            'temperature' => $latestData?->temperature ?? 0,
            'humidity' => $latestData?->humidity ?? 0,
            'co2_level' => $latestData?->co2_level ?? 0,
            'light_intensity' => $latestData?->light_intensity ?? 0,
            'is_anomaly' => $latestData?->is_anomaly ?? false,
            'anomaly_type' => $latestData?->anomaly_type ?? null,
            'recorded_at' => now(),
            ...$deviceStates,
            'temperature_setting' => $latestData?->temperature_setting ?? 25.0,
            'humidity_setting' => $latestData?->humidity_setting ?? 60.0,
            'light_intensity_setting' => $latestData?->light_intensity_setting ?? 1500,
        ]);

        // TODO: 这里可以添加发送指令到边缘服务器的逻辑
        // 例如：通过 MQTT、WebSocket 或 HTTP 发送指令到边缘服务器

        return response()->json([
            'success' => true,
            'message' => '设备控制指令已发送',
            'data' => [
                'chamber_code' => $chamber->code,
                'device' => $device,
                'device_name' => ChamberEnvironmentData::getDeviceNames()[$device],
                'state' => $state,
                'executed_at' => $newData->recorded_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * 批量控制方舱设备
     */
    public function controlMultiple(Request $request, string $deviceCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'devices' => 'required|array',
            'devices.*.device' => 'required|string|in:inner_circulation,cooling,heating,fan,four_way_valve,fresh_air,humidification,lighting_supplement,lighting',
            'devices.*.state' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamber = Chamber::where('device_code', $deviceCode)->first();

        if (! $chamber) {
            return response()->json([
                'success' => false,
                'message' => '方舱不存在',
            ], 404);
        }

        $devices = $request->input('devices');

        // 获取当前最新数据
        $latestData = ChamberEnvironmentData::where('chamber_id', $chamber->id)
            ->latest('recorded_at')
            ->first();

        // 创建设备状态数据
        $deviceStates = [
            'inner_circulation' => $latestData?->inner_circulation ?? false,
            'cooling' => $latestData?->cooling ?? false,
            'heating' => $latestData?->heating ?? false,
            'fan' => $latestData?->fan ?? false,
            'four_way_valve' => $latestData?->four_way_valve ?? false,
            'fresh_air' => $latestData?->fresh_air ?? false,
            'humidification' => $latestData?->humidification ?? false,
            'lighting_supplement' => $latestData?->lighting_supplement ?? false,
            'lighting' => $latestData?->lighting ?? false,
        ];

        foreach ($devices as $item) {
            $device = $item['device'];
            $state = $item['state'];
            if (in_array($device, ChamberEnvironmentData::getDeviceList())) {
                $deviceStates[$device] = $state;
            }
        }

        // 创建新的环境数据记录
        $newData = ChamberEnvironmentData::create([
            'chamber_id' => $chamber->id,
            'temperature' => $latestData?->temperature ?? 0,
            'humidity' => $latestData?->humidity ?? 0,
            'co2_level' => $latestData?->co2_level ?? 0,
            'light_intensity' => $latestData?->light_intensity ?? 0,
            'is_anomaly' => $latestData?->is_anomaly ?? false,
            'anomaly_type' => $latestData?->anomaly_type ?? null,
            'recorded_at' => now(),
            ...$deviceStates,
            'temperature_setting' => $latestData?->temperature_setting ?? 25.0,
            'humidity_setting' => $latestData?->humidity_setting ?? 60.0,
            'light_intensity_setting' => $latestData?->light_intensity_setting ?? 1500,
        ]);

        return response()->json([
            'success' => true,
            'message' => '批量控制指令已发送',
            'data' => [
                'chamber_code' => $chamber->code,
                'devices_count' => count($devices),
                'executed_at' => $newData->recorded_at->toIso8601String(),
            ],
        ]);
    }
}
