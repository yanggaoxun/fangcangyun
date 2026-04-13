<?php

namespace App\Admin\Resources\Mushroom\Batches\Pages;

use App\Admin\Resources\Mushroom\Batches\BatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;

    public function getTitle(): string
    {
        return '批次列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('新建批次'),
        ];
    }
}
