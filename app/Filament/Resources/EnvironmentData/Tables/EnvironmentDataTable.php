<?php

namespace App\Filament\Resources\EnvironmentData\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnvironmentDataTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chamber.base.name')
                    ->label('基地')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('chamber.name')
                    ->label('方舱')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('temperature')
                    ->label('温度')
                    ->suffix(' °C')
                    ->sortable(),

                TextColumn::make('humidity')
                    ->label('湿度')
                    ->suffix(' %')
                    ->sortable(),

                TextColumn::make('co2_level')
                    ->label('CO2浓度')
                    ->suffix(' ppm')
                    ->sortable(),

                TextColumn::make('ph_level')
                    ->label('pH值')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('light_intensity')
                    ->label('光照强度')
                    ->suffix(' lux')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('soil_moisture')
                    ->label('土壤湿度')
                    ->suffix(' %')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_anomaly')
                    ->label('异常')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('recorded_at')
                    ->label('记录时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('备注')
                    ->limit(30)
                    ->toggleable()
                    ->wrap(),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->filters([
                SelectFilter::make('chamber')
                    ->label('方舱')
                    ->relationship('chamber', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('recorded_at')
                    ->label('记录时间')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('开始日期')
                            ->format('Y年 n月j日'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('结束日期')
                            ->format('Y年 n月j日'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('recorded_at', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date) => $query->whereDate('recorded_at', '<=', $date));
                    }),

                SelectFilter::make('is_anomaly')
                    ->label('异常状态')
                    ->options([
                        '1' => '异常',
                        '0' => '正常',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('查看'),
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
