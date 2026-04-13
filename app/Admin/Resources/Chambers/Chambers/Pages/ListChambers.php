<?php

namespace App\Admin\Resources\Chambers\Chambers\Pages;

use App\Admin\Resources\Chambers\Chambers\ChamberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use UnitEnum;

class ListChambers extends ListRecords
{
    protected static string $resource = ChamberResource::class;

    protected static string|UnitEnum|null $navigationGroup = '方舱管理';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return '方舱列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建方舱'),
        ];
    }
}
