<?php

namespace App\Admin\Resources\Devices\Devices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('设备编号')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('设备名称')
                    ->searchable(),

                TextColumn::make('base.name')
                    ->label('所属基地')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('chamber.name')
                    ->label('所属方舱')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                BadgeColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => '正常',
                        'inactive' => '停用',
                        'maintenance' => '维护中',
                        'error' => '故障',
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'error',
                        'warning' => 'maintenance',
                        'gray' => 'inactive',
                    ]),

                TextColumn::make('serial_number')
                    ->label('序列号')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '正常',
                        'inactive' => '停用',
                        'maintenance' => '维护中',
                        'error' => '故障',
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
