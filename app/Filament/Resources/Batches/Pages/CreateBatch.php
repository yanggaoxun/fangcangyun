<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use App\Models\Base;
use App\Models\Chamber;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    public function getTitle(): string
    {
        return '新建批次';
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 生成批次编号
        if (isset($data['chamber_id'])) {
            $chamber = Chamber::find($data['chamber_id']);
            if ($chamber && $chamber->base) {
                $data['code'] = $chamber->base->code.$chamber->code.now()->format('Ymd');
            }
        }

        // 获取基地和菌种信息
        $baseId = $data['base_id'] ?? null;
        $strainId = $data['strain_id'] ?? null;
        $quantity = $data['strain_quantity'] ?? 0;

        if ($baseId && $strainId && $quantity > 0) {
            $base = Base::find($baseId);

            if ($base) {
                $stock = $base->getStrainStock($strainId);

                // 检查库存是否充足
                if (! $stock || ! $stock->hasEnoughStock($quantity)) {
                    $available = $stock ? $stock->available_quantity : 0;
                    Notification::make()
                        ->title('库存不足')
                        ->body("该基地菌种库存不足，当前可用: {$available}")
                        ->danger()
                        ->send();

                    throw new \Exception('库存不足');
                }

                // 扣减基地库存
                if (! $stock->deduct($quantity)) {
                    Notification::make()
                        ->title('库存扣减失败')
                        ->body('无法扣减库存，请稍后重试')
                        ->danger()
                        ->send();

                    throw new \Exception('库存扣减失败');
                }
            }
        }

        // 移除 base_id，因为数据库表中没有这个字段
        unset($data['base_id']);

        $record = static::getModel()::create($data);

        // 保存成功后更新方舱状态为种植中
        if ($record->chamber) {
            $record->chamber->update(['status' => 'planting']);
        }

        Notification::make()
            ->title('创建成功')
            ->body('批次已创建，菌种库存已扣减')
            ->success()
            ->send();

        return $record;
    }
}
