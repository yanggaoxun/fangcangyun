<?php

namespace Database\Seeders;

use App\Models\SysPermission;
use App\Models\SysRole;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        SysPermission::initSystemPermissions();
        $this->command->info('系统权限已初始化');

        SysRole::initSystemRoles();
        $this->command->info('系统角色已初始化');

        $firstUser = SysUser::first();
        if ($firstUser) {
            $superAdminRole = SysRole::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $firstUser->syncRoles(['super_admin']);
                $this->command->info('已为第一个用户分配超级管理员角色');
            }
        }
    }
}
