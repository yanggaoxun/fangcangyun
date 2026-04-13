<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamberControlState extends Model
{
    use HasFactory;

    protected $table = 'chambers_control_states';

    protected $fillable = [
        'chamber_id',
        'control_type',
        'current_state',
        'current_mode',
        'last_switch_at',
        'next_switch_at',
        'is_manual_override',
        'override_until',
    ];

    protected $casts = [
        'current_state' => 'boolean',
        'last_switch_at' => 'datetime',
        'next_switch_at' => 'datetime',
        'is_manual_override' => 'boolean',
        'override_until' => 'datetime',
    ];

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(ChamberControlConfig::class, 'chamber_id', 'chamber_id')
            ->where('control_type', $this->control_type);
    }

    /**
     * 获取或创建状态记录
     */
    public static function getOrCreate(int $chamberId, string $controlType): self
    {
        return self::firstOrCreate(
            ['chamber_id' => $chamberId, 'control_type' => $controlType],
            [
                'current_state' => false,
                'current_mode' => 'off',
                'last_switch_at' => now(),
                'is_manual_override' => false,
            ]
        );
    }

    /**
     * 设置设备状态
     */
    public function setDeviceState(bool $state, string $mode = 'auto', bool $isManual = false, ?int $overrideMinutes = null): void
    {
        $this->current_state = $state;
        $this->current_mode = $mode;
        $this->last_switch_at = now();

        if ($isManual) {
            $this->is_manual_override = true;
            $this->override_until = $overrideMinutes ? now()->addMinutes($overrideMinutes) : null;
        } else {
            $this->is_manual_override = false;
            $this->override_until = null;
        }

        $this->save();
    }

    /**
     * 检查是否被手动覆盖
     */
    public function isManualOverride(): bool
    {
        if (! $this->is_manual_override) {
            return false;
        }

        // 如果设置了过期时间且已过期，则取消覆盖
        if ($this->override_until && now()->greaterThan($this->override_until)) {
            $this->is_manual_override = false;
            $this->override_until = null;
            $this->save();

            return false;
        }

        return true;
    }

    /**
     * 计算下次切换时间（循环模式）
     */
    public function calculateNextSwitchAt(int $duration, string $unit): void
    {
        $this->next_switch_at = now()->add(
            $unit === 'seconds' ? $duration : $duration * 60,
            'seconds'
        );
        $this->save();
    }

    /**
     * 检查是否到达下次切换时间
     */
    public function shouldSwitch(): bool
    {
        return $this->next_switch_at && now()->greaterThanOrEqualTo($this->next_switch_at);
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
     * 作用域：当前开启状态
     */
    public function scopeOn($query)
    {
        return $query->where('current_state', true);
    }
}
