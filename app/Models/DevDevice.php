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
        'type',
        'status',
        'chamber_id',
        'brand',
        'model',
        'serial_number',
        'specifications',
        'is_automated',
        'last_maintenance_at',
        'installed_at',
        'notes',
    ];

    protected $casts = [
        'specifications' => 'array',
        'is_automated' => 'boolean',
        'last_maintenance_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

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

    public function scopeAutomated($query)
    {
        return $query->where('is_automated', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function needsMaintenance(): bool
    {
        if (! $this->last_maintenance_at) {
            return true;
        }

        // Check if it's been more than 30 days since last maintenance
        return $this->last_maintenance_at->diffInDays(now()) > 30;
    }
}
