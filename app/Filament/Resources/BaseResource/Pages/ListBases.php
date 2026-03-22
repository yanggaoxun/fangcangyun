<?php

namespace App\Filament\Resources\BaseResource\Pages;

use App\Filament\Resources\BaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBases extends ListRecords
{
    protected static string $resource = BaseResource::class;

    public function getTitle(): string
    {
        return '基地列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建基地'),
        ];
    }
}
