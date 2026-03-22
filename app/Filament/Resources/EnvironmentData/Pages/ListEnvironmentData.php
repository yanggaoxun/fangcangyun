<?php

namespace App\Filament\Resources\EnvironmentData\Pages;

use App\Filament\Resources\EnvironmentData\EnvironmentDataResource;
use Filament\Resources\Pages\ListRecords;

class ListEnvironmentData extends ListRecords
{
    protected static string $resource = EnvironmentDataResource::class;

    public function getTitle(): string
    {
        return '环境数据';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
