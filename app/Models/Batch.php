<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'chamber_id',
        'strain_id',
        'inoculation_date',
        'expected_harvest_date',
        'actual_harvest_date',
        'stage',
        'substrate_quantity',
        'expected_yield',
        'actual_yield',
        'status',
        'notes',
    ];

    protected $casts = [
        'inoculation_date' => 'date',
        'expected_harvest_date' => 'date',
        'actual_harvest_date' => 'date',
        'substrate_quantity' => 'integer',
        'expected_yield' => 'decimal:2',
        'actual_yield' => 'decimal:2',
    ];

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function strain(): BelongsTo
    {
        return $this->belongsTo(MushroomStrain::class);
    }

    public function getYieldRateAttribute(): ?float
    {
        if ($this->expected_yield > 0) {
            return round(($this->actual_yield / $this->expected_yield) * 100, 2);
        }
        return null;
    }

    public function getDaysSinceInoculationAttribute(): int
    {
        return $this->inoculation_date->diffInDays(now());
    }

    public function getExpectedDaysToHarvestAttribute(): ?int
    {
        if ($this->expected_harvest_date) {
            return now()->diffInDays($this->expected_harvest_date, false);
        }
        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHarvested($query)
    {
        return $query->where('status', 'completed')
            ->whereNotNull('actual_harvest_date');
    }
}
