<?php

namespace App\Admin\Resources\Mushroom\Batches\Pages;

use App\Admin\Resources\Mushroom\Batches\BatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBatch extends EditRecord
{
    protected static string $resource = BatchResource::class;

    public function getTitle(): string
    {
        return '编辑批次';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // 保存批次数据
        $record->update($data);

        // 检查是否满足释放方舱的条件
        // 条件：实际采收日期时间不为空 且 实际产量大于0
        if (! empty($data['actual_harvest_date']) && isset($data['actual_yield']) && $data['actual_yield'] > 0) {
            // 更新方舱状态为空闲
            if ($record->chamber) {
                $record->chamber->update(['status' => 'idle']);
            }
        }

        return $record;
    }
}
