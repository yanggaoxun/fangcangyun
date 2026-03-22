<?php

namespace App\Filament\Resources\Chambers\Pages;

use App\Filament\Resources\Chambers\ChamberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChambers extends ListRecords
{
    protected static string $resource = ChamberResource::class;

    public function getTitle(): string
    {
        return '方舱列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建方舱'),
        ];
    }
}
