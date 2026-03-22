<?php

namespace App\Filament\Resources\MushroomStrains\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class MushroomStrainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('code')
                    ->label('菌种编号')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\BadgeColumn::make('type')
                    ->label('菌种')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    }),

                \Filament\Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('库存数量')
                    ->suffix(fn ($record) => ' '.$record->unit)
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('temp_range')
                    ->label('温度范围 (°C)')
                    ->getStateUsing(function ($record) {
                        if ($record->temp_min === null && $record->temp_max === null) {
                            return '未设置';
                        }

                        return ($record->temp_min ?? '-').' ~ '.($record->temp_max ?? '-');
                    }),

                \Filament\Tables\Columns\TextColumn::make('humidity_range')
                    ->label('湿度范围 (%)')
                    ->getStateUsing(function ($record) {
                        if ($record->humidity_min === null && $record->humidity_max === null) {
                            return '未设置';
                        }

                        return ($record->humidity_min ?? '-').' ~ '.($record->humidity_max ?? '-');
                    }),

                \Filament\Tables\Columns\TextColumn::make('co2_range')
                    ->label('CO2范围 (ppm)')
                    ->getStateUsing(function ($record) {
                        if ($record->co2_min === null && $record->co2_max === null) {
                            return '未设置';
                        }

                        return ($record->co2_min ?? '-').' ~ '.($record->co2_max ?? '-');
                    }),

                \Filament\Tables\Columns\TextColumn::make('ph_range')
                    ->label('pH范围')
                    ->getStateUsing(function ($record) {
                        if ($record->ph_min === null && $record->ph_max === null) {
                            return '未设置';
                        }

                        return ($record->ph_min ?? '-').' ~ '.($record->ph_max ?? '-');
                    }),

                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('状态')
                    ->boolean(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->label('菌种')
                    ->options([
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('是否启用'),
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
