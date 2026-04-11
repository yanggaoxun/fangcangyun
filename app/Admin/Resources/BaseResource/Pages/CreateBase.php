<?php

namespace App\Admin\Resources\BaseResource\Pages;

use App\Admin\Resources\BaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBase extends CreateRecord
{
    protected static string $resource = BaseResource::class;

    public function getTitle(): string
    {
        return '新建基地';
    }
}
