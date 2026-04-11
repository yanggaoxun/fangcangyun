<?php

namespace App\Admin\Resources\Users\Pages;

use App\Admin\Resources\Users\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return '角色列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新增角色'),
        ];
    }
}
