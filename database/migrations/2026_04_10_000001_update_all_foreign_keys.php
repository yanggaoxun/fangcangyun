<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 获取表的外键信息
     */
    private function getForeignKeys(string $table): array
    {
        $foreignKeys = [];
        $database = DB::getDatabaseName();

        $results = DB::select('
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ', [$database, $table]);

        foreach ($results as $row) {
            $foreignKeys[$row->COLUMN_NAME] = [
                'name' => $row->CONSTRAINT_NAME,
                'referenced_table' => $row->REFERENCED_TABLE_NAME,
                'referenced_column' => $row->REFERENCED_COLUMN_NAME,
            ];
        }

        return $foreignKeys;
    }

    /**
     * 删除外键（如果不存在则忽略）
     */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $foreignKeys = $this->getForeignKeys($table);

        if (isset($foreignKeys[$column])) {
            Schema::table($table, function (Blueprint $table) use ($foreignKeys, $column) {
                $table->dropForeign($foreignKeys[$column]['name']);
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==================== 更新 chambers_chambers 表外键 ====================
        $this->dropForeignKeyIfExists('chambers_chambers', 'base_id');
        Schema::table('chambers_chambers', function (Blueprint $table) {
            $table->foreign('base_id')
                ->references('id')
                ->on('chambers_bases')
                ->onDelete('cascade')
                ->comment('所属基地');
        });

        // ==================== 更新 sys_users 表外键 ====================
        $this->dropForeignKeyIfExists('sys_users', 'base_id');
        Schema::table('sys_users', function (Blueprint $table) {
            $table->foreign('base_id')
                ->nullable()
                ->references('id')
                ->on('chambers_bases')
                ->nullOnDelete()
                ->comment('所属基地，基地管理员使用');
        });

        // ==================== 更新 mush_stocks 表外键 ====================
        $this->dropForeignKeyIfExists('mush_stocks', 'base_id');
        $this->dropForeignKeyIfExists('mush_stocks', 'strain_id');
        Schema::table('mush_stocks', function (Blueprint $table) {
            $table->foreign('base_id')
                ->references('id')
                ->on('chambers_bases')
                ->onDelete('cascade');

            $table->foreign('strain_id')
                ->references('id')
                ->on('mush_strains')
                ->onDelete('cascade');
        });

        // ==================== 更新 mush_batches 表外键 ====================
        $this->dropForeignKeyIfExists('mush_batches', 'chamber_id');
        Schema::table('mush_batches', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 dev_devices 表外键 ====================
        $this->dropForeignKeyIfExists('dev_devices', 'chamber_id');
        Schema::table('dev_devices', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 dev_controls 表外键 ====================
        $this->dropForeignKeyIfExists('dev_controls', 'device_id');
        $this->dropForeignKeyIfExists('dev_controls', 'user_id');
        Schema::table('dev_controls', function (Blueprint $table) {
            $table->foreign('device_id')
                ->references('id')
                ->on('dev_devices')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('sys_users')
                ->nullOnDelete();
        });

        // ==================== 更新 sys_alerts 表外键 ====================
        $this->dropForeignKeyIfExists('sys_alerts', 'chamber_id');
        $this->dropForeignKeyIfExists('sys_alerts', 'acknowledged_by');
        Schema::table('sys_alerts', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');

            $table->foreign('acknowledged_by')
                ->nullable()
                ->references('id')
                ->on('sys_users')
                ->nullOnDelete();
        });

        // ==================== 更新 chambers_environment_data 表外键 ====================
        $this->dropForeignKeyIfExists('chambers_environment_data', 'chamber_id');
        Schema::table('chambers_environment_data', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 chambers_control_configs 表外键 ====================
        $this->dropForeignKeyIfExists('chambers_control_configs', 'chamber_id');
        Schema::table('chambers_control_configs', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 chambers_schedules 表外键 ====================
        $this->dropForeignKeyIfExists('chambers_schedules', 'chamber_id');
        Schema::table('chambers_schedules', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 chambers_control_logs 表外键 ====================
        $this->dropForeignKeyIfExists('chambers_control_logs', 'chamber_id');
        Schema::table('chambers_control_logs', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 chambers_control_states 表外键 ====================
        $this->dropForeignKeyIfExists('chambers_control_states', 'chamber_id');
        Schema::table('chambers_control_states', function (Blueprint $table) {
            $table->foreign('chamber_id')
                ->references('id')
                ->on('chambers_chambers')
                ->onDelete('cascade');
        });

        // ==================== 更新 sys_user_role 关联表外键 ====================
        $this->dropForeignKeyIfExists('sys_user_role', 'user_id');
        $this->dropForeignKeyIfExists('sys_user_role', 'role_id');
        Schema::table('sys_user_role', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('sys_users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('sys_roles')
                ->onDelete('cascade');
        });

        // ==================== 更新 sys_role_permission 关联表外键 ====================
        $this->dropForeignKeyIfExists('sys_role_permission', 'role_id');
        $this->dropForeignKeyIfExists('sys_role_permission', 'permission_id');
        Schema::table('sys_role_permission', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('sys_roles')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('sys_permissions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢复所有外键到原始表名（生产环境很少需要回滚）
    }
};
