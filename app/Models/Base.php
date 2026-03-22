<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Base extends Model
{
    protected $fillable = [
        'code',
        'name',
        'location',
        'manager',
        'phone',
        'description',
        'status',
    ];

    public function chambers(): HasMany
    {
        return $this->hasMany(Chamber::class);
    }

    public function strainStocks(): HasMany
    {
        return $this->hasMany(BaseStrainStock::class);
    }

    /**
     * 获取基地特定菌种的库存
     */
    public function getStrainStock(int $strainId): ?BaseStrainStock
    {
        return $this->strainStocks()->where('strain_id', $strainId)->first();
    }

    /**
     * 初始化或获取菌种库存记录
     */
    public function getOrCreateStrainStock(int $strainId): BaseStrainStock
    {
        return $this->strainStocks()->firstOrCreate(
            ['strain_id' => $strainId],
            ['stock_quantity' => 0, 'reserved_quantity' => 0]
        );
    }
}
