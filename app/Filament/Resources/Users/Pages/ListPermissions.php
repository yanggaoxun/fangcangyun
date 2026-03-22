<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\PermissionResource;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return '权限列表';
    }
}
