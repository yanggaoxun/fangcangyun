<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 系统管理模块
        $this->renamePermission('users.view', 'system.users.view');
        $this->renamePermission('users.create', 'system.users.create');
        $this->renamePermission('users.edit', 'system.users.edit');
        $this->renamePermission('users.delete', 'system.users.delete');

        $this->renamePermission('roles.view', 'system.roles.view');
        $this->renamePermission('roles.create', 'system.roles.create');
        $this->renamePermission('roles.edit', 'system.roles.edit');
        $this->renamePermission('roles.delete', 'system.roles.delete');

        $this->renamePermission('permissions.view', 'system.permissions.view');
        $this->renamePermission('permissions.create', 'system.permissions.create');
        $this->renamePermission('permissions.edit', 'system.permissions.edit');
        $this->renamePermission('permissions.delete', 'system.permissions.delete');

        $this->renamePermission('alerts.view', 'system.alerts.view');
        $this->renamePermission('alerts.create', 'system.alerts.create');
        $this->renamePermission('alerts.edit', 'system.alerts.edit');
        $this->renamePermission('alerts.delete', 'system.alerts.delete');

        // 设备管理模块
        $this->renamePermission('devices.view', 'devices.devices.view');
        $this->renamePermission('devices.create', 'devices.devices.create');
        $this->renamePermission('devices.edit', 'devices.devices.edit');
        $this->renamePermission('devices.delete', 'devices.devices.delete');

        // 菌菇管理模块
        $this->renamePermission('strains.view', 'mushroom.strains.view');
        $this->renamePermission('strains.create', 'mushroom.strains.create');
        $this->renamePermission('strains.edit', 'mushroom.strains.edit');
        $this->renamePermission('strains.delete', 'mushroom.strains.delete');

        $this->renamePermission('batches.view', 'mushroom.batches.view');
        $this->renamePermission('batches.create', 'mushroom.batches.create');
        $this->renamePermission('batches.edit', 'mushroom.batches.edit');
        $this->renamePermission('batches.delete', 'mushroom.batches.delete');

        // 方舱管理模块
        // 基地管理
        $this->renamePermission('bases.view', 'chambers.bases.view');
        $this->renamePermission('bases.create', 'chambers.bases.create');
        $this->renamePermission('bases.edit', 'chambers.bases.edit');
        $this->renamePermission('bases.delete', 'chambers.bases.delete');

        // 方舱管理
        $this->renamePermission('chambers.view', 'chambers.chambers.view');
        $this->renamePermission('chambers.create', 'chambers.chambers.create');
        $this->renamePermission('chambers.edit', 'chambers.chambers.edit');
        $this->renamePermission('chambers.delete', 'chambers.chambers.delete');

        $this->renamePermission('environment.view', 'chambers.monitor.view');
        $this->renamePermission('auto_control.view', 'chambers.auto_control.view');
        $this->renamePermission('auto_control.config', 'chambers.auto_control.edit');
        $this->renamePermission('auto_control.manual', 'chambers.manual_control.view');
        $this->renamePermission('auto_control.logs', 'chambers.control_log.view');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚逻辑（反向操作）
        $this->renamePermission('system.users.view', 'users.view');
        $this->renamePermission('system.users.create', 'users.create');
        $this->renamePermission('system.users.edit', 'users.edit');
        $this->renamePermission('system.users.delete', 'users.delete');

        $this->renamePermission('system.roles.view', 'roles.view');
        $this->renamePermission('system.roles.create', 'roles.create');
        $this->renamePermission('system.roles.edit', 'roles.edit');
        $this->renamePermission('system.roles.delete', 'roles.delete');

        $this->renamePermission('system.permissions.view', 'permissions.view');
        $this->renamePermission('system.permissions.create', 'permissions.create');
        $this->renamePermission('system.permissions.edit', 'permissions.edit');
        $this->renamePermission('system.permissions.delete', 'permissions.delete');

        $this->renamePermission('system.alerts.view', 'alerts.view');
        $this->renamePermission('system.alerts.create', 'alerts.create');
        $this->renamePermission('system.alerts.edit', 'alerts.edit');
        $this->renamePermission('system.alerts.delete', 'alerts.delete');

        $this->renamePermission('devices.devices.view', 'devices.view');
        $this->renamePermission('devices.devices.create', 'devices.create');
        $this->renamePermission('devices.devices.edit', 'devices.edit');
        $this->renamePermission('devices.devices.delete', 'devices.delete');

        $this->renamePermission('mushroom.strains.view', 'strains.view');
        $this->renamePermission('mushroom.strains.create', 'strains.create');
        $this->renamePermission('mushroom.strains.edit', 'strains.edit');
        $this->renamePermission('mushroom.strains.delete', 'strains.delete');

        $this->renamePermission('mushroom.batches.view', 'batches.view');
        $this->renamePermission('mushroom.batches.create', 'batches.create');
        $this->renamePermission('mushroom.batches.edit', 'batches.edit');
        $this->renamePermission('mushroom.batches.delete', 'batches.delete');

        // 基地管理
        $this->renamePermission('chambers.bases.view', 'bases.view');
        $this->renamePermission('chambers.bases.create', 'bases.create');
        $this->renamePermission('chambers.bases.edit', 'bases.edit');
        $this->renamePermission('chambers.bases.delete', 'bases.delete');

        // 方舱管理
        $this->renamePermission('chambers.chambers.view', 'chambers.view');
        $this->renamePermission('chambers.chambers.create', 'chambers.create');
        $this->renamePermission('chambers.chambers.edit', 'chambers.edit');
        $this->renamePermission('chambers.chambers.delete', 'chambers.delete');

        $this->renamePermission('chambers.monitor.view', 'environment.view');
        $this->renamePermission('chambers.auto_control.view', 'auto_control.view');
        $this->renamePermission('chambers.auto_control.edit', 'auto_control.config');
        $this->renamePermission('chambers.manual_control.view', 'auto_control.manual');
        $this->renamePermission('chambers.control_log.view', 'auto_control.logs');
    }

    /**
     * 重命名权限
     */
    private function renamePermission(string $oldName, string $newName): void
    {
        DB::table('sys_permissions')
            ->where('name', $oldName)
            ->update(['name' => $newName]);
    }
};
