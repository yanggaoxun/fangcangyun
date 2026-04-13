<?php

namespace App\Admin\Resources\System\Users\Pages;

use App\Admin\Resources\System\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return '用户列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新增用户'),
        ];
    }
}
