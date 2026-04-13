<?php

namespace App\Admin\Resources\Chambers\BaseResources\Pages;

use App\Admin\Resources\Chambers\BaseResources\BaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBase extends CreateRecord
{
    protected static string $resource = BaseResource::class;

    public function getTitle(): string
    {
        return '新建基地';
    }
}
