<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chamber extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'capacity',
        'type',
        'status',
        'description',
        'images',
        'target_temperature',
        'target_humidity',
        'target_co2',
        'target_ph',
    ];

    protected $casts = [
        'images' => 'array',
        'target_temperature' => 'decimal:2',
        'target_humidity' => 'decimal:2',
        'target_co2' => 'decimal:2',
        'target_ph' => 'decimal:2',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function environmentData(): HasMany
    {
        return $this->hasMany(EnvironmentData::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function getCurrentBatchAttribute()
    {
        return $this->batches()->where('status', 'active')->first();
    }
}
