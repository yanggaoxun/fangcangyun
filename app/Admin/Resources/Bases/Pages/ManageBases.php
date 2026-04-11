<?php

namespace App\Admin\Resources\Bases\Pages;

use App\Admin\Resources\Bases\BaseResource;
use App\Models\Base;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ManageBases extends ManageRecords
{
    protected static string $resource = BaseResource::class;

    public function getTitle(): string
    {
        return '基地管理';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建基地'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Base $record */
        // 检查状态是否从 active 改为 inactive 或 maintenance
        if ($record->status === 'active' && in_array($data['status'] ?? '', ['inactive', 'maintenance'])) {
            // 检查是否有方舱处于种植中状态
            $plantingChambers = $record->chambers()
                ->where('status', 'planting')
                ->count();

            if ($plantingChambers > 0) {
                $message = "该基地下有 {$plantingChambers} 个方舱处于种植中状态，无法修改为";
                $message .= $data['status'] === 'inactive' ? '暂停使用' : '维护中';
                $message .= '。请先完成或转移这些方舱的种植任务。';

                Notification::make()
                    ->title('操作失败')
                    ->body($message)
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'status' => $message,
                ]);
            }
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
