<?php

namespace App\Filament\Resources\Alerts\Pages;

use App\Filament\Resources\Alerts\AlertResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    public function getTitle(): string
    {
        return '报警管理';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建报警'),
        ];
    }
}
