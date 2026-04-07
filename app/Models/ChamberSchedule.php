<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamberSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'chamber_id',
        'control_type',
        'schedule_index',
        'is_enabled',
        'start_time',
        'end_time',
        'temp_cooling_upper',
        'temp_cooling_lower',
        'temp_heating_upper',
        'temp_heating_lower',
        'humidity_upper',
        'humidity_lower',
        'co2_upper',
        'co2_lower',
        'cycle_run_minutes',
        'cycle_stop_minutes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'schedule_index' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
        'temp_cooling_upper' => 'decimal:2',
        'temp_cooling_lower' => 'decimal:2',
        'temp_heating_upper' => 'decimal:2',
        'temp_heating_lower' => 'decimal:2',
        'humidity_upper' => 'decimal:2',
        'humidity_lower' => 'decimal:2',
        'co2_upper' => 'integer',
        'co2_lower' => 'integer',
        'cycle_run_minutes' => 'integer',
        'cycle_stop_minutes' => 'integer',
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
     * 检查当前时间是否在该时段内
     */
    public function isCurrentTime(): bool
    {
        $now = now();
        $start = now()->setTimeFromTimeString($this->start_time);
        $end = now()->setTimeFromTimeString($this->end_time);

        // 处理跨天的情况（如 22:00 - 06:00）
        if ($end->lessThan($start)) {
            $end->addDay();
            if ($now->lessThan($start)) {
                $start->subDay();
                $end->subDay();
            }
        }

        return $now->between($start, $end);
    }

    /**
     * 作用域：启用的时段
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 作用域：当前有效的时段
     */
    public function scopeCurrent($query)
    {
        $now = now()->format('H:i:s');

        return $query->where('is_enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereTime('start_time', '<=', $now)
                    ->whereTime('end_time', '>=', $now);
            })
            ->orWhere(function ($q) use ($now) {
                // 跨天时段
                $q->whereTime('start_time', '>', 'end_time')
                    ->where(function ($sq) use ($now) {
                        $sq->whereTime('start_time', '<=', $now)
                            ->orWhereTime('end_time', '>=', $now);
                    });
            });
    }
}
