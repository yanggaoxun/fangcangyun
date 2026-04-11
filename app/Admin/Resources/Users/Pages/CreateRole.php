<?php

namespace App\Admin\Resources\Users\Pages;

use App\Admin\Resources\Users\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return '新增角色';
    }
}
