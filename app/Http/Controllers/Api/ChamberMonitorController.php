<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chamber;
use App\Models\ChamberEnvironmentData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChamberMonitorController extends Controller
{
    /**
     * Get environment data list with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_id' => 'nullable|integer|exists:chamber_bases,id',
            'chamber_id' => 'nullable|integer|exists:chambers,id',
            'is_anomaly' => 'nullable|boolean',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = ChamberEnvironmentData::query()
            ->with(['chamber.base', 'chamber']);

        // Apply filters
        if ($request->has('base_id')) {
            $query->whereHas('chamber', function ($q) use ($request) {
                $q->where('base_id', $request->base_id);
            });
        }

        if ($request->has('chamber_id')) {
            $query->where('chamber_id', $request->chamber_id);
        }

        if ($request->has('is_anomaly')) {
            $query->where('is_anomaly', $request->is_anomaly);
        }

        if ($request->has('from')) {
            $query->whereDate('recorded_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->whereDate('recorded_at', '<=', $request->to);
        }

        $perPage = $request->input('per_page', 15);
        $data = $query->orderBy('recorded_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get latest environment data for each chamber
     */
    public function latest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_id' => 'nullable|integer|exists:chamber_bases,id',
            'chamber_id' => 'nullable|integer|exists:chambers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get latest record for each chamber
        $subQuery = ChamberEnvironmentData::select('chamber_id')
            ->selectRaw('MAX(recorded_at) as latest_recorded_at')
            ->groupBy('chamber_id');

        $query = ChamberEnvironmentData::query()
            ->with(['chamber.base', 'chamber'])
            ->joinSub($subQuery, 'latest', function ($join) {
                $join->on('chamber_environment_data.chamber_id', '=', 'latest.chamber_id')
                    ->on('chamber_environment_data.recorded_at', '=', 'latest.latest_recorded_at');
            });

        // Apply filters
        if ($request->has('base_id')) {
            $query->whereHas('chamber', function ($q) use ($request) {
                $q->where('base_id', $request->base_id);
            });
        }

        if ($request->has('chamber_id')) {
            $query->where('chamber_environment_data.chamber_id', $request->chamber_id);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Store environment data from a chamber
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'chamber_code' => 'required|string|exists:chambers,code',
            'temperature' => 'required|numeric|between:-50,100',
            'humidity' => 'required|numeric|between:0,100',
            'co2_level' => 'required|numeric|min:0',
            'ph_level' => 'nullable|numeric|between:0,14',
            'light_intensity' => 'nullable|numeric|min:0',
            'soil_moisture' => 'nullable|numeric|between:0,100',
            'recorded_at' => 'nullable|date',
            'is_anomaly' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'chamber_code.required' => '方舱代码不能为空',
            'chamber_code.exists' => '方舱代码不存在',
            'temperature.required' => '温度不能为空',
            'temperature.between' => '温度必须在 -50°C 到 100°C 之间',
            'humidity.required' => '湿度不能为空',
            'humidity.between' => '湿度必须在 0% 到 100% 之间',
            'co2_level.required' => 'CO2浓度不能为空',
            'ph_level.between' => 'pH值必须在 0 到 14 之间',
            'soil_moisture.between' => '土壤湿度必须在 0% 到 100% 之间',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the chamber
        $chamber = Chamber::where('code', $request->chamber_code)->first();

        if (! $chamber) {
            return response()->json([
                'success' => false,
                'message' => '方舱不存在',
            ], 404);
        }

        // Check if chamber is in maintenance mode
        if ($chamber->status === 'maintenance') {
            return response()->json([
                'success' => false,
                'message' => '方舱维护中，无法接收数据',
            ], 403);
        }

        // Create the environment data record
        try {
            $environmentData = ChamberEnvironmentData::create([
                'chamber_id' => $chamber->id,
                'temperature' => $request->temperature,
                'humidity' => $request->humidity,
                'co2_level' => $request->co2_level,
                'ph_level' => $request->ph_level,
                'light_intensity' => $request->light_intensity,
                'soil_moisture' => $request->soil_moisture,
                'recorded_at' => $request->recorded_at ?? now(),
                'is_anomaly' => $request->is_anomaly ?? false,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => '环境数据记录成功',
                'data' => [
                    'id' => $environmentData->id,
                    'chamber_id' => $environmentData->chamber_id,
                    'recorded_at' => $environmentData->recorded_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '数据保存失败：'.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch store multiple environment data records
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chamber_code' => 'required|string|exists:chambers,code',
            'data' => 'required|array|min:1',
            'data.*.temperature' => 'required|numeric|between:-50,100',
            'data.*.humidity' => 'required|numeric|between:0,100',
            'data.*.co2_level' => 'required|numeric|min:0',
            'data.*.ph_level' => 'nullable|numeric|between:0,14',
            'data.*.light_intensity' => 'nullable|numeric|min:0',
            'data.*.soil_moisture' => 'nullable|numeric|between:0,100',
            'data.*.recorded_at' => 'nullable|date',
            'data.*.is_anomaly' => 'nullable|boolean',
            'data.*.notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamber = Chamber::where('code', $request->chamber_code)->first();

        if (! $chamber) {
            return response()->json([
                'success' => false,
                'message' => '方舱不存在',
            ], 404);
        }

        // Check if chamber is in maintenance mode
        if ($chamber->status === 'maintenance') {
            return response()->json([
                'success' => false,
                'message' => '方舱维护中，无法接收数据',
            ], 403);
        }

        $createdRecords = [];
        $errors = [];

        foreach ($request->data as $index => $dataPoint) {
            try {
                $environmentData = ChamberEnvironmentData::create([
                    'chamber_id' => $chamber->id,
                    'temperature' => $dataPoint['temperature'],
                    'humidity' => $dataPoint['humidity'],
                    'co2_level' => $dataPoint['co2_level'],
                    'ph_level' => $dataPoint['ph_level'] ?? null,
                    'light_intensity' => $dataPoint['light_intensity'] ?? null,
                    'soil_moisture' => $dataPoint['soil_moisture'] ?? null,
                    'recorded_at' => $dataPoint['recorded_at'] ?? now(),
                    'is_anomaly' => $dataPoint['is_anomaly'] ?? false,
                    'notes' => $dataPoint['notes'] ?? null,
                ]);

                $createdRecords[] = [
                    'index' => $index,
                    'id' => $environmentData->id,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($errors) === 0
                ? '所有数据记录成功'
                : '部分数据记录失败',
            'data' => [
                'created_count' => count($createdRecords),
                'failed_count' => count($errors),
                'created' => $createdRecords,
                'errors' => $errors,
            ],
        ], count($errors) === 0 ? 201 : 207);
    }
}
