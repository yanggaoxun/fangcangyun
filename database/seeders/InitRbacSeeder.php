<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitRbacSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('开始初始化RBAC数据...');

        // 1. 创建系统权限
        $permissions = [
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
            // 环境监控
            ['name' => 'environment.view', 'label' => '查看环境数据', 'group' => '环境监控'],
            ['name' => 'environment.create', 'label' => '添加环境数据', 'group' => '环境监控'],
            ['name' => 'environment.edit', 'label' => '编辑环境数据', 'group' => '环境监控'],
            ['name' => 'environment.delete', 'label' => '删除环境数据', 'group' => '环境监控'],
            // 报警管理
            ['name' => 'alerts.view', 'label' => '查看报警', 'group' => '报警管理'],
            ['name' => 'alerts.create', 'label' => '创建报警', 'group' => '报警管理'],
            ['name' => 'alerts.edit', 'label' => '编辑报警', 'group' => '报警管理'],
            ['name' => 'alerts.delete', 'label' => '删除报警', 'group' => '报警管理'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }
        $this->command->info('权限数据已创建');

        // 2. 创建系统角色
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['label' => '超级管理员', 'description' => '拥有所有权限']
        );

        $systemAdmin = Role::firstOrCreate(
            ['name' => 'system_admin'],
            ['label' => '系统管理员', 'description' => '管理系统用户和基地']
        );

        $baseAdmin = Role::firstOrCreate(
            ['name' => 'base_admin'],
            ['label' => '基地管理员', 'description' => '管理所属基地的数据']
        );

        $this->command->info('角色数据已创建');

        // 3. 为角色分配权限
        // 超级管理员拥有所有权限
        $allPermissionIds = Permission::pluck('id')->toArray();
        $superAdmin->permissions()->sync($allPermissionIds);
        $this->command->info('超级管理员权限已分配');

        // 系统管理员拥有除角色/权限管理外的所有权限
        $systemAdminPermissions = Permission::whereNotIn('group', ['角色管理', '权限管理'])->pluck('id')->toArray();
        $systemAdmin->permissions()->sync($systemAdminPermissions);
        $this->command->info('系统管理员权限已分配');

        // 基地管理员拥有基础权限
        $baseAdminPermissionNames = [
            'chambers.view', 'chambers.edit',
            'strains.view',
            'batches.view', 'batches.create', 'batches.edit',
            'devices.view', 'devices.edit',
            'environment.view',
            'alerts.view', 'alerts.edit',
        ];
        $baseAdminPermissionIds = Permission::whereIn('name', $baseAdminPermissionNames)->pluck('id')->toArray();
        $baseAdmin->permissions()->sync($baseAdminPermissionIds);
        $this->command->info('基地管理员权限已分配');

        // 4. 为第一个用户分配超级管理员角色
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->roles()->sync([$superAdmin->id]);
            $this->command->info('已为第一个用户(ID: '.$firstUser->id.')分配超级管理员角色');
        } else {
            $this->command->warn('没有找到用户，请确保 users 表中有数据');
        }

        $this->command->info('RBAC初始化完成！');
    }
}
