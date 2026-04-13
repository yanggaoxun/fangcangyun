<?php

namespace App\Admin\Resources\Chambers\Chambers\Pages;

use App\Admin\Resources\Chambers\Chambers\ChamberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChamber extends CreateRecord
{
    protected static string $resource = ChamberResource::class;

    public function getTitle(): string
    {
        return '新建方舱';
    }
}
