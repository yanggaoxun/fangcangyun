<?php

namespace App\Filament\Resources\MushroomStrains\Pages;

use App\Filament\Resources\MushroomStrains\MushroomStrainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMushroomStrains extends ListRecords
{
    protected static string $resource = MushroomStrainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
