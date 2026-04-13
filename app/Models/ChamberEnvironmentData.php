<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamberEnvironmentData extends Model
{
    use HasFactory;

    protected $table = 'chambers_monitor';

    protected $fillable = [
        'chamber_id',
        'temperature',
        'humidity',
        'co2_level',
        'ph_level',
        'light_intensity',
        'soil_moisture',
        'recorded_at',
        'is_anomaly',
        'notes',
        // 设备状态字段
        'inner_circulation',
        'cooling',
        'heating',
        'fan',
        'four_way_valve',
        'fresh_air',
        'humidification',
        'lighting_supplement',
        'lighting',
        // 设定值字段
        'temperature_setting',
        'humidity_setting',
        'light_intensity_setting',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'co2_level' => 'decimal:2',
        'ph_level' => 'decimal:2',
        'light_intensity' => 'decimal:2',
        'soil_moisture' => 'decimal:2',
        'recorded_at' => 'datetime',
        'is_anomaly' => 'boolean',
        // 设备状态
        'inner_circulation' => 'boolean',
        'cooling' => 'boolean',
        'heating' => 'boolean',
        'fan' => 'boolean',
        'four_way_valve' => 'boolean',
        'fresh_air' => 'boolean',
        'humidification' => 'boolean',
        'lighting_supplement' => 'boolean',
        'lighting' => 'boolean',
        // 设定值
        'temperature_setting' => 'decimal:2',
        'humidity_setting' => 'decimal:2',
        'light_intensity_setting' => 'integer',
    ];

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeAnomalies($query)
    {
        return $query->where('is_anomaly', true);
    }

    public function getFormattedTemperatureAttribute(): string
    {
        return $this->temperature.' °C';
    }

    public function getFormattedHumidityAttribute(): string
    {
        return $this->humidity.' %';
    }

    public function getFormattedCo2LevelAttribute(): string
    {
        return $this->co2_level.' ppm';
    }

    public function getFormattedPhLevelAttribute(): ?string
    {
        return $this->ph_level ? $this->ph_level.' pH' : null;
    }

    /**
     * 获取设备列表
     */
    public static function getDeviceList(): array
    {
        return [
            'inner_circulation',
            'cooling',
            'heating',
            'fan',
            'four_way_valve',
            'fresh_air',
            'humidification',
            'lighting_supplement',
            'lighting',
        ];
    }

    /**
     * 获取设备中文名称
     */
    public static function getDeviceNames(): array
    {
        return [
            'inner_circulation' => '内循环',
            'cooling' => '制冷',
            'heating' => '加热',
            'fan' => '风机',
            'four_way_valve' => '四通阀',
            'fresh_air' => '新风',
            'humidification' => '加湿',
            'lighting_supplement' => '补光',
            'lighting' => '光照',
        ];
    }
}
