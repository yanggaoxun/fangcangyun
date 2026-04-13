<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SysPermission extends Model
{
    use HasFactory;

    protected $table = 'sys_permissions';

    protected $fillable = [
        'name',
        'label',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SysRole::class, 'sys_role_permission', 'permission_id', 'role_id');
    }

    /**
     * 预定义的系统权限
     */
    public static function getSystemPermissions(): array
    {
        return [
            // 用户管理
            ['name' => 'system.users.view', 'label' => '查看用户', 'group' => '用户管理'],
            ['name' => 'system.users.create', 'label' => '创建用户', 'group' => '用户管理'],
            ['name' => 'system.users.edit', 'label' => '编辑用户', 'group' => '用户管理'],
            ['name' => 'system.users.delete', 'label' => '删除用户', 'group' => '用户管理'],

            // 角色管理
            ['name' => 'system.roles.view', 'label' => '查看角色', 'group' => '角色管理'],
            ['name' => 'system.roles.create', 'label' => '创建角色', 'group' => '角色管理'],
            ['name' => 'system.roles.edit', 'label' => '编辑角色', 'group' => '角色管理'],
            ['name' => 'system.roles.delete', 'label' => '删除角色', 'group' => '角色管理'],

            // 权限管理
            ['name' => 'system.permissions.view', 'label' => '查看权限', 'group' => '权限管理'],
            ['name' => 'system.permissions.create', 'label' => '创建权限', 'group' => '权限管理'],
            ['name' => 'system.permissions.edit', 'label' => '编辑权限', 'group' => '权限管理'],
            ['name' => 'system.permissions.delete', 'label' => '删除权限', 'group' => '权限管理'],

            // 报警管理
            ['name' => 'system.alerts.view', 'label' => '查看报警', 'group' => '报警管理'],
            ['name' => 'system.alerts.create', 'label' => '创建报警', 'group' => '报警管理'],
            ['name' => 'system.alerts.edit', 'label' => '编辑报警', 'group' => '报警管理'],
            ['name' => 'system.alerts.delete', 'label' => '删除报警', 'group' => '报警管理'],

            // 设备管理
            ['name' => 'devices.devices.view', 'label' => '查看设备', 'group' => '设备管理'],
            ['name' => 'devices.devices.create', 'label' => '创建设备', 'group' => '设备管理'],
            ['name' => 'devices.devices.edit', 'label' => '编辑设备', 'group' => '设备管理'],
            ['name' => 'devices.devices.delete', 'label' => '删除设备', 'group' => '设备管理'],

            // 基地管理
            ['name' => 'chambers.bases.view', 'label' => '查看基地', 'group' => '基地管理'],
            ['name' => 'chambers.bases.create', 'label' => '创建基地', 'group' => '基地管理'],
            ['name' => 'chambers.bases.edit', 'label' => '编辑基地', 'group' => '基地管理'],
            ['name' => 'chambers.bases.delete', 'label' => '删除基地', 'group' => '基地管理'],

            // 方舱管理
            ['name' => 'chambers.chambers.view', 'label' => '查看方舱', 'group' => '方舱管理'],
            ['name' => 'chambers.chambers.create', 'label' => '创建方舱', 'group' => '方舱管理'],
            ['name' => 'chambers.chambers.edit', 'label' => '编辑方舱', 'group' => '方舱管理'],
            ['name' => 'chambers.chambers.delete', 'label' => '删除方舱', 'group' => '方舱管理'],

            // 菌种管理
            ['name' => 'mushroom.strains.view', 'label' => '查看菌种', 'group' => '菌种管理'],
            ['name' => 'mushroom.strains.create', 'label' => '创建菌种', 'group' => '菌种管理'],
            ['name' => 'mushroom.strains.edit', 'label' => '编辑菌种', 'group' => '菌种管理'],
            ['name' => 'mushroom.strains.delete', 'label' => '删除菌种', 'group' => '菌种管理'],

            // 批次管理
            ['name' => 'mushroom.batches.view', 'label' => '查看批次', 'group' => '批次管理'],
            ['name' => 'mushroom.batches.create', 'label' => '创建批次', 'group' => '批次管理'],
            ['name' => 'mushroom.batches.edit', 'label' => '编辑批次', 'group' => '批次管理'],
            ['name' => 'mushroom.batches.delete', 'label' => '删除批次', 'group' => '批次管理'],

            // 环境监控
            ['name' => 'chambers.monitor.view', 'label' => '查看环境数据', 'group' => '环境监控'],

            // 自动控制管理
            ['name' => 'chambers.auto_control.view', 'label' => '查看自动控制配置', 'group' => '自动控制管理'],
            ['name' => 'chambers.auto_control.edit', 'label' => '配置自动控制', 'group' => '自动控制管理'],
            ['name' => 'chambers.manual_control.view', 'label' => '手动控制设备', 'group' => '自动控制管理'],
            ['name' => 'chambers.control_log.view', 'label' => '查看控制日志', 'group' => '自动控制管理'],
        ];
    }

    /**
     * 初始化系统权限
     */
    public static function initSystemPermissions(): void
    {
        $permissions = self::getSystemPermissions();

        foreach ($permissions as $permission) {
            self::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
