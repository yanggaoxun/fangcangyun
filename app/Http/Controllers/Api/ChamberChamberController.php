<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chamber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChamberChamberController extends Controller
{
    /**
     * Check if user has permission to perform action
     */
    protected function checkPermission(string $permission): ?JsonResponse
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user();

        // Super admin bypass
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
     * Get chambers list with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.chambers.view')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'base_id' => 'nullable|integer|exists:chambers_bases,id',
            'status' => 'nullable|in:idle,planting,maintenance',
            'keyword' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Chamber::query()
            ->with(['base', 'batches' => function ($q) {
                $q->active()->latest();
            }])
            ->withCount('batches');

        if ($request->has('base_id')) {
            $query->where('base_id', $request->base_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $chambers = $query->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $chambers,
        ]);
    }

    /**
     * Get single chamber detail
     */
    public function show(Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.chambers.view')) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $chamber->load(['base', 'batches' => function ($q) {
                $q->latest() - \u003elimit(5);
            }, 'devices']),
        ]);
    }

    /**
     * Create new chamber
     */
    public function store(Request $request): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.chambers.create')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'base_id' => 'required|integer|exists:chambers_bases,id',
            'code' => 'required|string|max:50|unique:chambers_chambers,code',
            'name' => 'required|string|max:255|unique:chambers_chambers,name',
            'capacity' => 'required|integer|min:0',
            'status' => 'required|in:idle,planting,maintenance',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
        ], [
            'base_id.required' => '所属基地不能为空',
            'base_id.exists' => '所选基地不存在',
            'code.required' => '方舱编号不能为空',
            'code.unique' => '该方舱编号已存在',
            'name.required' => '方舱名称不能为空',
            'name.unique' => '该方舱名称已存在',
            'capacity.required' => '容量不能为空',
            'status.required' => '状态不能为空',
            'status.in' => '状态必须是 idle、planting 或 maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamber = Chamber::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => '方舱创建成功',
            'data' => $chamber->load('base'),
        ], 201);
    }

    /**
     * Update chamber
     */
    public function update(Request $request, Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.chambers.edit')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'base_id' => 'sometimes|required|integer|exists:chambers_bases,id',
            'code' => "sometimes|required|string|max:50|unique:chambers_chambers,code,{$chamber->id}",
            'name' => "sometimes|required|string|max:255|unique:chambers_chambers,name,{$chamber->id}",
            'capacity' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|in:idle,planting,maintenance',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
        ], [
            'code.unique' => '该方舱编号已存在',
            'name.unique' => '该方舱名称已存在',
            'status.in' => '状态必须是 idle、planting 或 maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamber->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => '方舱更新成功',
            'data' => $chamber->fresh()->load('base'),
        ]);
    }

    /**
     * Delete chamber
     */
    public function destroy(Chamber $chamber): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.chambers.delete')) {
            return $response;
        }

        // Check if chamber has active batches
        $activeBatches = $chamber->batches()->active()->count();
        if ($activeBatches > 0) {
            return response()->json([
                'success' => false,
                'message' => "该方舱下有 {$activeBatches} 个进行中的种植批次，无法删除",
            ], 422);
        }

        // Check if chamber has devices
        if ($chamber->devices()->exists()) {
            return response()->json([
                'success' => false,
                'message' => '该方舱下还有设备，无法删除',
            ], 422);
        }

        $chamber->delete();

        return response()->json([
            'success' => true,
            'message' => '方舱删除成功',
        ]);
    }
}
