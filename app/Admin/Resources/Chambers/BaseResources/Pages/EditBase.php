<?php

namespace App\Admin\Resources\Chambers\BaseResources\Pages;

use App\Admin\Resources\Chambers\BaseResources\BaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBase extends EditRecord
{
    protected static string $resource = BaseResource::class;

    public function getTitle(): string
    {
        return '编辑基地';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('删除'),
        ];
    }
}
