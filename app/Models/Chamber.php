<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chamber extends Model
{
    use HasFactory;

    protected $table = 'chambers_chambers';

    protected $fillable = [
        'code',
        'name',
        'base_id',
        'capacity',
        'status',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(MushroomBatch::class);
    }

    public function environmentData(): HasMany
    {
        return $this->hasMany(ChamberManualControl::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(DevDevice::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(SysAlert::class);
    }

    public function base(): BelongsTo
    {
        return $this->belongsTo(ChamberBase::class);
    }

    public function getCurrentBatchAttribute()
    {
        return $this->batches()->active()->first();
    }
}
