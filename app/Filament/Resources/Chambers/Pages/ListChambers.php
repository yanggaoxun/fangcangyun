<?php

namespace App\Filament\Resources\Chambers\Pages;

use App\Filament\Resources\Chambers\ChamberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChambers extends ListRecords
{
    protected static string $resource = ChamberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
