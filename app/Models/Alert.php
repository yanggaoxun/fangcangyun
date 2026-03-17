<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'chamber_id',
        'type',
        'level',
        'title',
        'message',
        'trigger_value',
        'threshold_value',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgement_note',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'trigger_value' => 'decimal:2',
        'threshold_value' => 'decimal:2',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    public function acknowledge(User $user, string $note = null): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
            'acknowledgement_note' => $note,
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    public function getIsActiveAttribute(): bool
    {
        return !$this->is_resolved;
    }

    public function getPriorityLevelAttribute(): string
    {
        return match($this->level) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary'
        };
    }
}
