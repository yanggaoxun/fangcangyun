<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaseStrainStock extends Model
{
    use HasFactory;

    protected $table = 'base_strain_stocks';

    protected $fillable = [
        'base_id',
        'strain_id',
        'stock_quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
    ];

    public function base(): BelongsTo
    {
        return $this->belongsTo(Base::class);
    }

    public function strain(): BelongsTo
    {
        return $this->belongsTo(MushroomStrain::class);
    }

    /**
     * 获取可用库存（总库存减去预留）
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    /**
     * 检查是否有足够库存
     */
    public function hasEnoughStock(int $quantity): bool
    {
        return $this->available_quantity >= $quantity;
    }

    /**
     * 预留库存
     */
    public function reserve(int $quantity): bool
    {
        if (! $this->hasEnoughStock($quantity)) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->save();

        return true;
    }

    /**
     * 释放预留库存
     */
    public function release(int $quantity): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();
    }

    /**
     * 扣减库存（实际使用）
     */
    public function deduct(int $quantity): bool
    {
        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->stock_quantity -= $quantity;
        // 同时减少预留（如果有）
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();

        return true;
    }

    /**
     * 增加库存
     */
    public function add(int $quantity): void
    {
        $this->stock_quantity += $quantity;
        $this->save();
    }
}
