<?php

namespace App\Filament\Resources\EnvironmentData\Pages;

use App\Filament\Resources\EnvironmentData\EnvironmentDataResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEnvironmentData extends EditRecord
{
    protected static string $resource = EnvironmentDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
