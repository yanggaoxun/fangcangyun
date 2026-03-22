<?php

namespace App\Filament\Resources\Chambers\Pages;

use App\Filament\Resources\Chambers\ChamberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChamber extends EditRecord
{
    protected static string $resource = ChamberResource::class;

    public function getTitle(): string
    {
        return '编辑方舱';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }
}
