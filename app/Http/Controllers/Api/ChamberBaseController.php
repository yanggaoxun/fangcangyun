<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChamberBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChamberBaseController extends Controller
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
     * Get bases list with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.bases.view')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:active,inactive,maintenance',
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

        $query = ChamberBase::query()
            ->withCount('chambers');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $bases = $query->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bases,
        ]);
    }

    /**
     * Get single base detail
     */
    public function show(ChamberBase $base): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.bases.view')) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $base->load(['chambers' => function ($query) {
                $query->select('id', 'base_id', 'name', 'code', 'status');
            }]),
        ]);
    }

    /**
     * Create new base
     */
    public function store(Request $request): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.bases.create')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:chambers_bases,code',
            'name' => 'required|string|max:100',
            'location' => 'required|string|max:200',
            'manager' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance',
        ], [
            'code.required' => '基地编号不能为空',
            'code.unique' => '基地编号已存在',
            'name.required' => '基地名称不能为空',
            'location.required' => '基地位置不能为空',
            'status.required' => '状态不能为空',
            'status.in' => '状态必须是 active、inactive 或 maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        $base = ChamberBase::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => '基地创建成功',
            'data' => $base,
        ], 201);
    }

    /**
     * Update base
     */
    public function update(Request $request, ChamberBase $base): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.bases.edit')) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'code' => "sometimes|required|string|max:50|unique:chambers_bases,code,{$base->id}",
            'name' => 'sometimes|required|string|max:100',
            'location' => 'sometimes|required|string|max:200',
            'manager' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive,maintenance',
        ], [
            'code.unique' => '基地编号已存在',
            'status.in' => '状态必须是 active、inactive 或 maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check business rule: cannot change status if has planting chambers
        if ($request->has('status')
            && $base->status === 'active'
            && in_array($request->status, ['inactive', 'maintenance'])) {
            $plantingChambers = $base->chambers()->where('status', 'planting')->count();
            if ($plantingChambers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "该基地下有 {$plantingChambers} 个方舱处于种植中状态，无法修改状态",
                ], 422);
            }
        }

        $base->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => '基地更新成功',
            'data' => $base,
        ]);
    }

    /**
     * Delete base
     */
    public function destroy(ChamberBase $base): JsonResponse
    {
        if ($response = $this->checkPermission('chambers.bases.delete')) {
            return $response;
        }

        // Check if base has chambers
        if ($base->chambers()->exists()) {
            return response()->json([
                'success' => false,
                'message' => '该基地下还有方舱，无法删除',
            ], 422);
        }

        $base->delete();

        return response()->json([
            'success' => true,
            'message' => '基地删除成功',
        ]);
    }
}
