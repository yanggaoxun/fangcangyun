<?php

namespace App\Admin\Resources\MushroomStrains\Pages;

use App\Admin\Resources\MushroomStrains\MushroomStrainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMushroomStrains extends ListRecords
{
    protected static string $resource = MushroomStrainResource::class;

    public function getTitle(): string
    {
        return '菌种管理';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建菌种'),
        ];
    }
}
