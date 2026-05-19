<?php

namespace App\Admin\Resources\Chambers\Chambers\Pages;

use App\Admin\Resources\Chambers\Chambers\ChamberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChamber extends EditRecord
{
    protected static string $resource = ChamberResource::class;

    public function getTitle(): string
    {
        return '编辑方舱';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '方舱已保存';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }
}
