<?php

namespace App\Filament\Resources\EnvironmentData\Pages;

use App\Filament\Resources\EnvironmentData\EnvironmentDataResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnvironmentData extends ListRecords
{
    protected static string $resource = EnvironmentDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
