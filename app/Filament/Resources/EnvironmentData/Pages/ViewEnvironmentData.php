<?php

namespace App\Filament\Resources\EnvironmentData\Pages;

use App\Filament\Resources\EnvironmentData\EnvironmentDataResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEnvironmentData extends ViewRecord
{
    protected static string $resource = EnvironmentDataResource::class;

    public function getTitle(): string
    {
        return '查看环境数据';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
