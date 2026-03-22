<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\PermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return '新增权限';
    }
}
