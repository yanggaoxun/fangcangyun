<?php

namespace App\Admin\Resources\System\Alerts\Pages;

use App\Admin\Resources\System\Alerts\AlertResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAlert extends EditRecord
{
    protected static string $resource = AlertResource::class;

    public function getTitle(): string
    {
        return '编辑报警';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }
}
