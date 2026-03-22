<?php

namespace App\Filament\Resources\Batches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('批次编号')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chamber.base.name')
                    ->label('所属基地')
                    ->searchable(),

                TextColumn::make('chamber.name')
                    ->label('方舱')
                    ->searchable(),

                TextColumn::make('strain.name')
                    ->label('菌种')
                    ->searchable(),

                TextColumn::make('strain_quantity')
                    ->label('菌类数量')
                    ->suffix(fn ($record) => ' '.$record->strain->unit)
                    ->sortable(),

                TextColumn::make('inoculation_date')
                    ->label('接种时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('expected_harvest_date')
                    ->label('预计采收时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('actual_harvest_date')
                    ->label('实际采收时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('expected_yield')
                    ->label('预计产量')
                    ->suffix(' kg'),

                TextColumn::make('actual_yield')
                    ->label('实际产量')
                    ->suffix(' kg'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('编辑'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('删除选中'),
                ])
                    ->label('批量操作'),
            ]);
    }
}
