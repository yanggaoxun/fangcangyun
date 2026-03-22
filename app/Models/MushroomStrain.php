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
        //'name',
        'scientific_name',
        'type',
        'supplier',
        'production_date',
        'expiration_date',
        'growth_cycle',
        'unit',
        'description',
        'storage_conditions',
        'is_active',
        'temp_min',
        'temp_max',
        'humidity_min',
        'humidity_max',
        'co2_min',
        'co2_max',
        'ph_min',
        'ph_max',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiration_date' => 'date',
        'growth_cycle' => 'integer',
        'storage_conditions' => 'array',
        'is_active' => 'boolean',
        'temp_min' => 'decimal:2',
        'temp_max' => 'decimal:2',
        'humidity_min' => 'decimal:2',
        'humidity_max' => 'decimal:2',
        'co2_min' => 'decimal:2',
        'co2_max' => 'decimal:2',
        'ph_min' => 'decimal:2',
        'ph_max' => 'decimal:2',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function baseStocks(): HasMany
    {
        return $this->hasMany(BaseStrainStock::class, 'strain_id');
    }

    /**
     * 获取在特定基地的库存
     */
    public function getStockInBase(int $baseId): ?BaseStrainStock
    {
        return $this->baseStocks()->where('base_id', $baseId)->first();
    }

    /**
     * 获取总库存（所有基地）
     */
    public function getTotalStockAttribute(): int
    {
        return $this->baseStocks()->sum('stock_quantity');
    }

    /**
     * 获取可用的基地ID列表（有库存的基地）
     */
    public function getAvailableBaseIds(): array
    {
        return $this->baseStocks()
            ->where('stock_quantity', '>', 0)
            ->pluck('base_id')
            ->toArray();
    }

    /**
     * 检查在特定基地是否有足够库存
     */
    public function hasEnoughStockInBase(int $baseId, int $quantity): bool
    {
        $stock = $this->getStockInBase($baseId);

        return $stock ? $stock->hasEnoughStock($quantity) : false;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
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
