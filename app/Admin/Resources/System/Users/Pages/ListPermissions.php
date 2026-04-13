<?php

namespace App\Admin\Resources\System\Users\Pages;

use App\Admin\Resources\System\Users\PermissionResource;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return '权限列表';
    }
}
