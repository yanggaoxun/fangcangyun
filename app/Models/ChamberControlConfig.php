<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChamberControlConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'chamber_id',
        'control_type',
        'mode',
        'is_enabled',
        'cycle_run_duration',
        'cycle_run_unit',
        'cycle_stop_duration',
        'cycle_stop_unit',
        'threshold_upper',
        'threshold_lower',
        'threshold_sensor',
        'linkage_config',
        'delay_seconds',
        'delay_cooling_heating',
        'delay_stop_cycle',
        'inner_cycle_run',
        'inner_cycle_stop',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'cycle_run_duration' => 'integer',
        'cycle_stop_duration' => 'integer',
        'threshold_upper' => 'decimal:2',
        'threshold_lower' => 'decimal:2',
        'linkage_config' => 'array',
        'delay_seconds' => 'integer',
        'delay_cooling_heating' => 'integer',
        'delay_stop_cycle' => 'integer',
        'inner_cycle_run' => 'integer',
        'inner_cycle_stop' => 'integer',
    ];

    /**
     * 控制类型常量
     */
    const CONTROL_TYPES = [
        'temperature' => '温度',
        'humidity' => '湿度',
        'fresh_air' => '新风',
        'exhaust' => '排风',
        'lighting' => '光照',
        'inner_circulation' => '内循环',
    ];

    /**
     * 模式常量
     */
    const MODES = [
        'off' => '关闭',
        'manual' => '手动控制',
        'auto_cycle' => '自动-启停循环',
        'auto_threshold' => '自动-上下限',
        'auto_schedule' => '自动-时间段',
    ];

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ChamberSchedule::class, 'chamber_id', 'chamber_id')
            ->where('control_type', $this->control_type);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(ChamberControlState::class, 'chamber_id', 'chamber_id')
            ->where('control_type', $this->control_type);
    }

    /**
     * 是否自动模式
     */
    public function isAutoMode(): bool
    {
        return in_array($this->mode, ['auto_cycle', 'auto_threshold', 'auto_schedule']);
    }

    /**
     * 获取联动配置
     */
    public function getLinkageConfig(): array
    {
        return $this->linkage_config ?? [
            'link_inner_circulation' => true,
            'link_exhaust' => false,
            'link_fresh_air' => false,
        ];
    }

    /**
     * 获取或创建配置
     */
    public static function getOrCreate(int $chamberId, string $controlType): self
    {
        return self::firstOrCreate(
            ['chamber_id' => $chamberId, 'control_type' => $controlType],
            [
                'mode' => 'off',
                'is_enabled' => false,
                'cycle_run_duration' => 30,
                'cycle_run_unit' => 'minutes',
                'cycle_stop_duration' => 30,
                'cycle_stop_unit' => 'minutes',
                'threshold_upper' => null,
                'threshold_lower' => null,
                'linkage_config' => json_encode([
                    'link_inner_circulation' => true,
                    'link_exhaust' => false,
                    'link_fresh_air' => false,
                ]),
                'delay_seconds' => 0,
            ]
        );
    }

    /**
     * 作用域：启用的配置
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 作用域：自动模式
     */
    public function scopeAutoMode($query)
    {
        return $query->whereIn('mode', ['auto_cycle', 'auto_threshold', 'auto_schedule']);
    }

    /**
     * 作用域：特定控制类型
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('control_type', $type);
    }
}
