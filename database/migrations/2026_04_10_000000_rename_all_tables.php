<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==================== 方舱管理表 (chambers_*) ====================
        Schema::rename('bases', 'chambers_bases');
        Schema::rename('chambers', 'chambers_chambers');
        Schema::rename('chamber_environment_data', 'chambers_environment_data');
        Schema::rename('chamber_control_configs', 'chambers_control_configs');
        Schema::rename('chamber_schedules', 'chambers_schedules');
        Schema::rename('chamber_control_logs', 'chambers_control_logs');
        Schema::rename('chamber_control_states', 'chambers_control_states');

        // ==================== 菌菇管理表 (mush_*) ====================
        Schema::rename('mushroom_strains', 'mush_strains');
        Schema::rename('batches', 'mush_batches');
        Schema::rename('base_strain_stocks', 'mush_stocks');

        // ==================== 设备管理表 (dev_*) ====================
        Schema::rename('devices', 'dev_devices');
        Schema::rename('device_controls', 'dev_controls');

        // ==================== 系统管理表 (sys_*) ====================
        Schema::rename('users', 'sys_users');
        Schema::rename('roles', 'sys_roles');
        Schema::rename('permissions', 'sys_permissions');
        Schema::rename('role_user', 'sys_user_role');
        Schema::rename('permission_role', 'sys_role_permission');
        Schema::rename('alerts', 'sys_alerts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ==================== 系统管理表 (sys_*) ====================
        Schema::rename('sys_alerts', 'alerts');
        Schema::rename('sys_role_permission', 'permission_role');
        Schema::rename('sys_user_role', 'role_user');
        Schema::rename('sys_permissions', 'permissions');
        Schema::rename('sys_roles', 'roles');
        Schema::rename('sys_users', 'users');

        // ==================== 设备管理表 (dev_*) ====================
        Schema::rename('dev_controls', 'device_controls');
        Schema::rename('dev_devices', 'devices');

        // ==================== 菌菇管理表 (mush_*) ====================
        Schema::rename('mush_stocks', 'base_strain_stocks');
        Schema::rename('mush_batches', 'batches');
        Schema::rename('mush_strains', 'mushroom_strains');

        // ==================== 方舱管理表 (chambers_*) ====================
        Schema::rename('chambers_control_states', 'chamber_control_states');
        Schema::rename('chambers_control_logs', 'chamber_control_logs');
        Schema::rename('chambers_schedules', 'chamber_schedules');
        Schema::rename('chambers_control_configs', 'chamber_control_configs');
        Schema::rename('chambers_environment_data', 'chamber_environment_data');
        Schema::rename('chambers_chambers', 'chambers');
        Schema::rename('chambers_bases', 'bases');
    }
};
