<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AutoControlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('开始初始化自动控制权限...');

        // 1. 创建自动控制权限
        $autoControlPermissions = [
            ['name' => 'auto_control.view', 'label' => '查看自动控制配置', 'group' => '自动控制管理'],
            ['name' => 'auto_control.config', 'label' => '配置自动控制', 'group' => '自动控制管理'],
            ['name' => 'auto_control.manual', 'label' => '手动控制设备', 'group' => '自动控制管理'],
            ['name' => 'auto_control.logs', 'label' => '查看控制日志', 'group' => '自动控制管理'],
        ];

        foreach ($autoControlPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }
        $this->command->info('自动控制权限已创建');

        // 2. 为角色分配自动控制权限
        $autoControlPermissionIds = Permission::whereIn('name', [
            'auto_control.view',
            'auto_control.config',
            'auto_control.manual',
            'auto_control.logs',
        ])->pluck('id')->toArray();

        // 为超级管理员分配所有自动控制权限
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($autoControlPermissionIds);
            $this->command->info('已为超级管理员分配自动控制权限');
        }

        // 为系统管理员分配所有自动控制权限
        $systemAdmin = Role::where('name', 'system_admin')->first();
        if ($systemAdmin) {
            $systemAdmin->permissions()->syncWithoutDetaching($autoControlPermissionIds);
            $this->command->info('已为系统管理员分配自动控制权限');
        }

        // 为基地管理员分配所有自动控制权限
        $baseAdmin = Role::where('name', 'base_admin')->first();
        if ($baseAdmin) {
            $baseAdmin->permissions()->syncWithoutDetaching($autoControlPermissionIds);
            $this->command->info('已为基地管理员分配自动控制权限');
        }

        $this->command->info('自动控制权限初始化完成！');
    }
}
