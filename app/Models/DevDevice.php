<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevDevice extends Model
{
    use HasFactory;

    protected $table = 'dev_devices';

    protected $fillable = [
        'code',
        'name',
        'status',
        'base_id',
        'chamber_id',
        'serial_number',
        'notes',
    ];

    protected $casts = [];

    public function base(): BelongsTo
    {
        return $this->belongsTo(ChamberBase::class);
    }

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function controls(): HasMany
    {
        return $this->hasMany(DeviceControl::class);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getLastControlAttribute()
    {
        return $this->controls()->latest()->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
