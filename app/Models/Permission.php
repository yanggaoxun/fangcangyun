<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    /**
     * 预定义的系统权限
     */
    public static function getSystemPermissions(): array
    {
        return [
            // 用户管理
            ['name' => 'users.view', 'label' => '查看用户', 'group' => '用户管理'],
            ['name' => 'users.create', 'label' => '创建用户', 'group' => '用户管理'],
            ['name' => 'users.edit', 'label' => '编辑用户', 'group' => '用户管理'],
            ['name' => 'users.delete', 'label' => '删除用户', 'group' => '用户管理'],

            // 角色管理
            ['name' => 'roles.view', 'label' => '查看角色', 'group' => '角色管理'],
            ['name' => 'roles.create', 'label' => '创建角色', 'group' => '角色管理'],
            ['name' => 'roles.edit', 'label' => '编辑角色', 'group' => '角色管理'],
            ['name' => 'roles.delete', 'label' => '删除角色', 'group' => '角色管理'],

            // 权限管理
            ['name' => 'permissions.view', 'label' => '查看权限', 'group' => '权限管理'],
            ['name' => 'permissions.create', 'label' => '创建权限', 'group' => '权限管理'],
            ['name' => 'permissions.edit', 'label' => '编辑权限', 'group' => '权限管理'],
            ['name' => 'permissions.delete', 'label' => '删除权限', 'group' => '权限管理'],

            // 基地管理
            ['name' => 'bases.view', 'label' => '查看基地', 'group' => '基地管理'],
            ['name' => 'bases.create', 'label' => '创建基地', 'group' => '基地管理'],
            ['name' => 'bases.edit', 'label' => '编辑基地', 'group' => '基地管理'],
            ['name' => 'bases.delete', 'label' => '删除基地', 'group' => '基地管理'],

            // 方舱管理
            ['name' => 'chambers.view', 'label' => '查看方舱', 'group' => '方舱管理'],
            ['name' => 'chambers.create', 'label' => '创建方舱', 'group' => '方舱管理'],
            ['name' => 'chambers.edit', 'label' => '编辑方舱', 'group' => '方舱管理'],
            ['name' => 'chambers.delete', 'label' => '删除方舱', 'group' => '方舱管理'],

            // 菌种管理
            ['name' => 'strains.view', 'label' => '查看菌种', 'group' => '菌种管理'],
            ['name' => 'strains.create', 'label' => '创建菌种', 'group' => '菌种管理'],
            ['name' => 'strains.edit', 'label' => '编辑菌种', 'group' => '菌种管理'],
            ['name' => 'strains.delete', 'label' => '删除菌种', 'group' => '菌种管理'],

            // 批次管理
            ['name' => 'batches.view', 'label' => '查看批次', 'group' => '批次管理'],
            ['name' => 'batches.create', 'label' => '创建批次', 'group' => '批次管理'],
            ['name' => 'batches.edit', 'label' => '编辑批次', 'group' => '批次管理'],
            ['name' => 'batches.delete', 'label' => '删除批次', 'group' => '批次管理'],

            // 设备管理
            ['name' => 'devices.view', 'label' => '查看设备', 'group' => '设备管理'],
            ['name' => 'devices.create', 'label' => '创建设备', 'group' => '设备管理'],
            ['name' => 'devices.edit', 'label' => '编辑设备', 'group' => '设备管理'],
            ['name' => 'devices.delete', 'label' => '删除设备', 'group' => '设备管理'],

            // 环境数据
            ['name' => 'environment.view', 'label' => '查看环境数据', 'group' => '环境监控'],
            ['name' => 'environment.create', 'label' => '添加环境数据', 'group' => '环境监控'],
            ['name' => 'environment.edit', 'label' => '编辑环境数据', 'group' => '环境监控'],
            ['name' => 'environment.delete', 'label' => '删除环境数据', 'group' => '环境监控'],

            // 报警管理
            ['name' => 'alerts.view', 'label' => '查看报警', 'group' => '报警管理'],
            ['name' => 'alerts.create', 'label' => '创建报警', 'group' => '报警管理'],
            ['name' => 'alerts.edit', 'label' => '编辑报警', 'group' => '报警管理'],
            ['name' => 'alerts.delete', 'label' => '删除报警', 'group' => '报警管理'],

            // 自动控制管理
            ['name' => 'auto_control.view', 'label' => '查看自动控制配置', 'group' => '自动控制管理'],
            ['name' => 'auto_control.config', 'label' => '配置自动控制', 'group' => '自动控制管理'],
            ['name' => 'auto_control.manual', 'label' => '手动控制设备', 'group' => '自动控制管理'],
            ['name' => 'auto_control.logs', 'label' => '查看控制日志', 'group' => '自动控制管理'],
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
