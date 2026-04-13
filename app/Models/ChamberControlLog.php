<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamberControlLog extends Model
{
    use HasFactory;

    protected $table = 'chambers_control_logs';

    protected $fillable = [
        'chamber_id',
        'control_type',
        'trigger_type',
        'trigger_reason',
        'action',
        'sensor_data',
        'config_snapshot',
        'executed_at',
        'executed_by',
    ];

    protected $casts = [
        'sensor_data' => 'array',
        'config_snapshot' => 'array',
        'executed_at' => 'datetime',
    ];

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'executed_by');
    }

    /**
     * 记录控制日志
     */
    public static function record(
        int $chamberId,
        string $controlType,
        string $triggerType,
        string $action,
        string $reason,
        ?array $sensorData = null,
        ?array $configSnapshot = null,
        ?int $executedBy = null
    ): self {
        return self::create([
            'chamber_id' => $chamberId,
            'control_type' => $controlType,
            'trigger_type' => $triggerType,
            'action' => $action,
            'trigger_reason' => $reason,
            'sensor_data' => $sensorData,
            'config_snapshot' => $configSnapshot,
            'executed_at' => now(),
            'executed_by' => $executedBy,
        ]);
    }

    /**
     * 作用域：特定方舱
     */
    public function scopeOfChamber($query, int $chamberId)
    {
        return $query->where('chamber_id', $chamberId);
    }

    /**
     * 作用域：特定控制类型
     */
    public function scopeOfType($query, string $controlType)
    {
        return $query->where('control_type', $controlType);
    }

    /**
     * 作用域：触发类型
     */
    public function scopeTriggerType($query, string $type)
    {
        return $query->where('trigger_type', $type);
    }

    /**
     * 作用域：最近N条
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('executed_at', 'desc')->limit($limit);
    }

    /**
     * 作用域：今天
     */
    public function scopeToday($query)
    {
        return $query->whereDate('executed_at', today());
    }

    /**
     * 获取触发类型标签
     */
    public function getTriggerTypeLabel(): string
    {
        return match ($this->trigger_type) {
            'auto' => '自动控制',
            'manual' => '手动控制',
            'linkage' => '联动触发',
            default => $this->trigger_type,
        };
    }

    /**
     * 获取动作标签
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            'turn_on' => '开启',
            'turn_off' => '关闭',
            default => $this->action,
        };
    }
}
