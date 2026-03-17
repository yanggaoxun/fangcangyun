<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MushroomStrain extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'scientific_name',
        'type',
        'supplier',
        'production_date',
        'expiration_date',
        'stock_quantity',
        'unit',
        'description',
        'storage_conditions',
        'is_active',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiration_date' => 'date',
        'stock_quantity' => 'integer',
        'storage_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('stock_quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isBefore(now());
    }
}
