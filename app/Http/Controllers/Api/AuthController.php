<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SysUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 用户登录
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = SysUser::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['邮箱或密码不正确'],
            ]);
        }

        // 检查用户状态（如果有状态字段）
        // if ($user->status !== 'active') {
        //     return response()->json([
        //         'message' => '账号已被禁用',
        //     ], 403);
        // }

        // 创建 token，使用设备名称区分不同设备
        $deviceName = $request->input('device_name', 'App');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => '登录成功',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'base_id' => $user->base_id,
                'roles' => $user->roles()->pluck('name'),
                'permissions' => $user->permissions(),
            ],
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request): JsonResponse
    {
        // 删除当前设备的 token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => '登出成功',
        ]);
    }

    /**
     * 登出所有设备
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // 删除用户的所有 token
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => '已登出所有设备',
        ]);
    }

    /**
     * 获取当前登录用户信息
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'base_id' => $user->base_id,
                'roles' => $user->roles()->pluck('name'),
                'permissions' => $user->permissions(),
            ],
        ]);
    }

    /**
     * 刷新 Token（可选）
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // 删除旧 token
        $request->user()->currentAccessToken()->delete();

        // 创建新 token
        $deviceName = $request->input('device_name', 'App');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Token 已刷新',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
