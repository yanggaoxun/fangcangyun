<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChamberBase extends Model
{
    protected $table = 'chambers_bases';

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
        return $this->hasMany(Chamber::class, 'base_id');
    }

    public function strainStocks(): HasMany
    {
        return $this->hasMany(MushroomStock::class, 'base_id');
    }

    /**
     * 获取基地特定菌种的库存
     */
    public function getStrainStock(int $strainId): ?MushroomStock
    {
        return $this->strainStocks()->where('strain_id', $strainId)->first();
    }

    /**
     * 初始化或获取菌种库存记录
     */
    public function getOrCreateStrainStock(int $strainId): MushroomStock
    {
        return $this->strainStocks()->firstOrCreate(
            ['strain_id' => $strainId],
            ['stock_quantity' => 0, 'reserved_quantity' => 0]
        );
    }
}
