<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
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

        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
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

        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->detach($permissionIds);
    }

    /**
     * 同步角色权限
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
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
        $allPermissions = Permission::pluck('name')->toArray();
        $superAdmin->syncPermissions($allPermissions);

        // 为系统管理员分配权限（除了角色和权限管理）
        $systemAdminPermissions = Permission::whereNotIn('group', ['角色管理', '权限管理'])
            ->pluck('name')
            ->toArray();
        $systemAdmin->syncPermissions($systemAdminPermissions);

        // 为基地管理员分配基础权限
        $baseAdminPermissions = [
            'chambers.view', 'chambers.edit',
            'strains.view',
            'batches.view', 'batches.create', 'batches.edit',
            'devices.view', 'devices.edit',
            'environment.view',
            'alerts.view', 'alerts.edit',
            'auto_control.view', 'auto_control.config', 'auto_control.manual', 'auto_control.logs',
        ];
        $baseAdmin->syncPermissions($baseAdminPermissions);
    }
}
