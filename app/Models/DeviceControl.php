<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'user_id',
        'action',
        'parameters',
        'source',
        'status',
        'executed_at',
        'result',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'parameters' => 'array',
        'executed_at' => 'datetime',
        'ip_address' => 'string',
        'user_agent' => 'string',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function markAsExecuted(string $result = null): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
            'result' => $result,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'executed_at' => now(),
            'result' => $error,
        ]);
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'on' => '开启',
            'off' => '关闭',
            'adjust' => '调节',
            'configure' => '配置',
            default => $this->action,
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            'manual' => '手动',
            'automatic' => '自动',
            'scheduled' => '定时',
            'api' => 'API',
            default => $this->source,
        };
    }
}
