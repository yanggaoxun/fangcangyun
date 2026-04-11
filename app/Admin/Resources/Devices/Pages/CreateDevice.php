<?php

namespace App\Admin\Resources\Devices\Pages;

use App\Admin\Resources\Devices\DeviceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDevice extends CreateRecord
{
    protected static string $resource = DeviceResource::class;

    public function getTitle(): string
    {
        return '创建设备';
    }
}
