<?php

namespace App\Filament\Resources\Chambers\Pages;

use App\Filament\Resources\Chambers\ChamberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChamber extends EditRecord
{
    protected static string $resource = ChamberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
