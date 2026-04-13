<?php

namespace App\Admin\Resources\System\Alerts\Pages;

use App\Admin\Resources\System\Alerts\AlertResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlert extends CreateRecord
{
    protected static string $resource = AlertResource::class;

    public function getTitle(): string
    {
        return '新建报警';
    }
}
