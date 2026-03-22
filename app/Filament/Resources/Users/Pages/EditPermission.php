<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\PermissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    public function getTitle(): string
    {
        return '编辑权限';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }
}
