<?php

namespace App\Filament\Resources\Alerts\Pages;

use App\Filament\Resources\Alerts\AlertResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlert extends CreateRecord
{
    protected static string $resource = AlertResource::class;

    public function getTitle(): string
    {
        return '新建报警';
    }
}
