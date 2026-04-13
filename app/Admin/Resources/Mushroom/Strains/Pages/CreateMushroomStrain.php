<?php

namespace App\Admin\Resources\Mushroom\Strains\Pages;

use App\Admin\Resources\Mushroom\Strains\MushroomStrainResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMushroomStrain extends CreateRecord
{
    protected static string $resource = MushroomStrainResource::class;

    public function getTitle(): string
    {
        return '新建菌种';
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 提取基地库存数据
        $baseStocks = $data['base_stocks'] ?? [];
        unset($data['base_stocks']);

        // 创建菌种记录
        $record = static::getModel()::create($data);

        // 创建基地库存记录
        if (! empty($baseStocks)) {
            foreach ($baseStocks as $stockData) {
                if (! empty($stockData['base_id'])) {
                    $base = ChamberBase::find($stockData['base_id']);
                    if ($base) {
                        $base->getOrCreateStrainStock($record->id)
                            ->add($stockData['stock_quantity'] ?? 0);
                    }
                }
            }

            Notification::make()
                ->title('创建成功')
                ->body('菌种已创建，基地库存已配置')
                ->success()
                ->send();
        }

        return $record;
    }
}
