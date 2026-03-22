<?php

namespace App\Filament\Resources\MushroomStrains\Pages;

use App\Filament\Resources\MushroomStrains\MushroomStrainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMushroomStrain extends EditRecord
{
    protected static string $resource = MushroomStrainResource::class;

    public function getTitle(): string
    {
        return '编辑菌种';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }
}
