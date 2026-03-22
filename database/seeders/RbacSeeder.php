<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        Permission::initSystemPermissions();
        $this->command->info('系统权限已初始化');

        Role::initSystemRoles();
        $this->command->info('系统角色已初始化');

        $firstUser = User::first();
        if ($firstUser) {
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $firstUser->syncRoles(['super_admin']);
                $this->command->info('已为第一个用户分配超级管理员角色');
            }
        }
    }
}
