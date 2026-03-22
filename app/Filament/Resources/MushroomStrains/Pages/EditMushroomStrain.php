<?php

namespace App\Filament\Resources\MushroomStrains\Pages;

use App\Filament\Resources\MushroomStrains\MushroomStrainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMushroomStrain extends EditRecord
{
    protected static string $resource = MushroomStrainResource::class;

    public function getTitle(): string
    {
        return '编辑菌种';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // 加载现有的基地库存数据
        $strain = $this->getRecord();
        $baseStocks = $strain->baseStocks()->with('base')->get();

        $this->form->fill([
            ...$this->form->getState(),
            'base_stocks' => $baseStocks->map(function ($stock) {
                return [
                    'base_id' => $stock->base_id,
                    'stock_quantity' => $stock->stock_quantity,
                ];
            })->toArray(),
        ]);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // 提取基地库存数据
        $baseStocks = $data['base_stocks'] ?? [];
        unset($data['base_stocks']);

        // 更新菌种基本信息
        $record->update($data);

        // 更新基地库存
        foreach ($baseStocks as $stockData) {
            if (! empty($stockData['base_id'])) {
                $stock = $record->baseStocks()->where('base_id', $stockData['base_id'])->first();
                if ($stock) {
                    // 更新现有库存
                    $stock->update(['stock_quantity' => $stockData['stock_quantity'] ?? 0]);
                } else {
                    // 创建新库存记录
                    $record->baseStocks()->create([
                        'base_id' => $stockData['base_id'],
                        'stock_quantity' => $stockData['stock_quantity'] ?? 0,
                        'reserved_quantity' => 0,
                    ]);
                }
            }
        }

        return $record;
    }
}
