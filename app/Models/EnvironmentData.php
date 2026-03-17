<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentData extends Model
{
    use HasFactory;

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
        return $this->temperature . ' °C';
    }

    public function getFormattedHumidityAttribute(): string
    {
        return $this->humidity . ' %';
    }

    public function getFormattedCo2LevelAttribute(): string
    {
        return $this->co2_level . ' ppm';
    }

    public function getFormattedPhLevelAttribute(): ?string
    {
        return $this->ph_level ? $this->ph_level . ' pH' : null;
    }
}
