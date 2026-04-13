<?php

namespace Database\Seeders;

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
            // 设备管理
            ['name' => 'devices.devices.view', 'label' => '查看设备', 'group' => '设备管理'],
            ['name' => 'devices.devices.create', 'label' => '创建设备', 'group' => '设备管理'],
            ['name' => 'devices.devices.edit', 'label' => '编辑设备', 'group' => '设备管理'],
            ['name' => 'devices.devices.delete', 'label' => '删除设备', 'group' => '设备管理'],
            // 环境监控
            ['name' => 'chambers.monitor.view', 'label' => '查看环境数据', 'group' => '环境监控'],
            ['name' => 'chambers.monitor.create', 'label' => '添加环境数据', 'group' => '环境监控'],
            ['name' => 'chambers.monitor.edit', 'label' => '编辑环境数据', 'group' => '环境监控'],
            ['name' => 'chambers.monitor.delete', 'label' => '删除环境数据', 'group' => '环境监控'],
            // 报警管理
            ['name' => 'system.alerts.view', 'label' => '查看报警', 'group' => '报警管理'],
            ['name' => 'system.alerts.create', 'label' => '创建报警', 'group' => '报警管理'],
            ['name' => 'system.alerts.edit', 'label' => '编辑报警', 'group' => '报警管理'],
            ['name' => 'system.alerts.delete', 'label' => '删除报警', 'group' => '报警管理'],
        ];

        foreach ($permissions as $perm) {
            SysPermission::firstOrCreate(['name' => $perm['name']], $perm);
        }
        $this->command->info('权限数据已创建');

        // 2. 创建系统角色
        $superAdmin = SysRole::firstOrCreate(
            ['name' => 'super_admin'],
            ['label' => '超级管理员', 'description' => '拥有所有权限']
        );

        $systemAdmin = SysRole::firstOrCreate(
            ['name' => 'system_admin'],
            ['label' => '系统管理员', 'description' => '管理系统用户和基地']
        );

        $baseAdmin = SysRole::firstOrCreate(
            ['name' => 'base_admin'],
            ['label' => '基地管理员', 'description' => '管理所属基地的数据']
        );

        $this->command->info('角色数据已创建');

        // 3. 为角色分配权限
        // 超级管理员拥有所有权限
        $allPermissionIds = SysPermission::pluck('id')->toArray();
        $superAdmin->permissions()->sync($allPermissionIds);
        $this->command->info('超级管理员权限已分配');

        // 系统管理员拥有除角色/权限管理外的所有权限
        $systemAdminPermissions = SysPermission::whereNotIn('group', ['角色管理', '权限管理'])->pluck('id')->toArray();
        $systemAdmin->permissions()->sync($systemAdminPermissions);
        $this->command->info('系统管理员权限已分配');

        // 基地管理员拥有基础权限
        $baseAdminPermissionNames = [
            'chambers.chambers.view', 'chambers.chambers.edit',
            'mushroom.strains.view',
            'mushroom.batches.view', 'mushroom.batches.create', 'mushroom.batches.edit',
            'devices.devices.view', 'devices.devices.edit',
            'chambers.monitor.view',
            'system.alerts.view', 'system.alerts.edit',
        ];
        $baseAdminPermissionIds = SysPermission::whereIn('name', $baseAdminPermissionNames)->pluck('id')->toArray();
        $baseAdmin->permissions()->sync($baseAdminPermissionIds);
        $this->command->info('基地管理员权限已分配');

        // 4. 为第一个用户分配超级管理员角色
        $firstUser = SysUser::first();
        if ($firstUser) {
            $firstUser->roles()->sync([$superAdmin->id]);
            $this->command->info('已为第一个用户(ID: '.$firstUser->id.')分配超级管理员角色');
        } else {
            $this->command->warn('没有找到用户，请确保 users 表中有数据');
        }

        $this->command->info('RBAC初始化完成！');
    }
}
