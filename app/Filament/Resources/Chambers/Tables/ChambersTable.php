<?php

namespace App\Filament\Resources\Chambers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChambersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('base.name')
                    ->label('所属基地')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('方舱编号')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('方舱名称')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->label('容量')
                    ->suffix(' 袋'),
                TextColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'idle' => '空闲',
                        'planting' => '种植中',
                        'maintenance' => '维护中',
                    }),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('base')
                    ->label('基地')
                    ->relationship('base', 'name'),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'idle' => '空闲',
                        'planting' => '种植中',
                        'maintenance' => '维护中',
                    ]),
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
