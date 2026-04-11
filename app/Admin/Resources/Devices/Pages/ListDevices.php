<?php

namespace App\Admin\Resources\Devices\Pages;

use App\Admin\Resources\Devices\DeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    public function getTitle(): string
    {
        return '设备列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('创建设备'),
        ];
    }
}
