<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SysRole extends Model
{
    use HasFactory;

    protected $table = 'sys_roles';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(SysPermission::class, 'sys_role_permission', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(SysUser::class, 'sys_user_role', 'role_id', 'user_id');
    }

    /**
     * 检查角色是否有指定权限
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * 为角色分配权限
     */
    public function givePermissionTo(string|array $permissions): void
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $permissionIds = SysPermission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * 撤销角色权限
     */
    public function revokePermissionTo(string|array $permissions): void
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $permissionIds = SysPermission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->detach($permissionIds);
    }

    /**
     * 同步角色权限
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = SysPermission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->sync($permissionIds);
    }

    /**
     * 初始化系统角色
     */
    public static function initSystemRoles(): void
    {
        // 超级管理员
        $superAdmin = self::firstOrCreate(
            ['name' => 'super_admin'],
            ['label' => '超级管理员', 'description' => '拥有所有权限']
        );

        // 系统管理员
        $systemAdmin = self::firstOrCreate(
            ['name' => 'system_admin'],
            ['label' => '系统管理员', 'description' => '管理系统用户和基地']
        );

        // 基地管理员
        $baseAdmin = self::firstOrCreate(
            ['name' => 'base_admin'],
            ['label' => '基地管理员', 'description' => '管理所属基地的数据']
        );

        // 为超级管理员分配所有权限
        $allPermissions = SysPermission::pluck('name')->toArray();
        $superAdmin->syncPermissions($allPermissions);

        // 为系统管理员分配权限（除了角色和权限管理）
        $systemAdminPermissions = SysPermission::whereNotIn('group', ['角色管理', '权限管理'])
            ->pluck('name')
            ->toArray();
        $systemAdmin->syncPermissions($systemAdminPermissions);

        // 为基地管理员分配基础权限
        $baseAdminPermissions = [
            'chambers.chambers.view', 'chambers.chambers.edit',
            'mushroom.strains.view',
            'mushroom.batches.view', 'mushroom.batches.create', 'mushroom.batches.edit',
            'devices.devices.view', 'devices.devices.edit',
            'chambers.monitor.view',
            'system.alerts.view', 'system.alerts.edit',
            'chambers.auto_control.view', 'chambers.auto_control.edit', 'chambers.manual_control.view', 'chambers.control_log.view',
        ];
        $baseAdmin->syncPermissions($baseAdminPermissions);
    }
}
