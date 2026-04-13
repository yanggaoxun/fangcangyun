<?php

namespace App\Admin\Resources\System\Users\Pages;

use App\Admin\Resources\System\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return '新增用户';
    }
}
