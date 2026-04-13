<?php

namespace App\Admin\Resources\Mushroom\Strains\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MushroomStrainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('菌种编号')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('菌种')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    })
                    ->colors([
                        'success' => 'oyster',
                        'primary' => 'shiitake',
                        'info' => 'enoki',
                        'gray' => 'other',
                    ]),

                TextColumn::make('total_stock')
                    ->label('总库存')
                    ->getStateUsing(function ($record) {
                        $total = $record->baseStocks()->sum('stock_quantity');

                        return $total.' '.$record->unit;
                    }),

                TextColumn::make('growth_cycle')
                    ->label('生长周期')
                    ->suffix(' 天')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('temp_range')
                    ->label('温度范围 (°C)')
                    ->getStateUsing(function ($record) {
                        if ($record->temp_min === null && $record->temp_max === null) {
                            return '未设置';
                        }

                        return ($record->temp_min ?? '-').' ~ '.($record->temp_max ?? '-');
                    })
                    ->toggleable(),

                TextColumn::make('humidity_range')
                    ->label('湿度范围 (%)')
                    ->getStateUsing(function ($record) {
                        if ($record->humidity_min === null && $record->humidity_max === null) {
                            return '未设置';
                        }

                        return ($record->humidity_min ?? '-').' ~ '.($record->humidity_max ?? '-');
                    })
                    ->toggleable(),

                TextColumn::make('co2_range')
                    ->label('CO2范围 (ppm)')
                    ->getStateUsing(function ($record) {
                        if ($record->co2_min === null && $record->co2_max === null) {
                            return '未设置';
                        }

                        return ($record->co2_min ?? '-').' ~ '.($record->co2_max ?? '-');
                    })
                    ->toggleable(),

                TextColumn::make('ph_range')
                    ->label('pH范围')
                    ->getStateUsing(function ($record) {
                        if ($record->ph_min === null && $record->ph_max === null) {
                            return '未设置';
                        }

                        return ($record->ph_min ?? '-').' ~ '.($record->ph_max ?? '-');
                    })
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('状态')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('菌种类型')
                    ->options([
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('启用状态'),

                Filter::make('has_stock')
                    ->label('库存状态')
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'in_stock') {
                            $query->whereHas('baseStocks', function (Builder $query) {
                                $query->where('stock_quantity', '>', 0);
                            });
                        } elseif ($data['value'] === 'out_of_stock') {
                            $query->whereDoesntHave('baseStocks', function (Builder $query) {
                                $query->where('stock_quantity', '>', 0);
                            });
                        }
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('value')
                            ->label('库存状态')
                            ->options([
                                'in_stock' => '有库存',
                                'out_of_stock' => '无库存',
                            ])
                            ->placeholder('全部'),
                    ]),

                Filter::make('growth_cycle')
                    ->label('生长周期')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min')
                            ->label('最小天数')
                            ->numeric(),
                        \Filament\Forms\Components\TextInput::make('max')
                            ->label('最大天数')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['min'], fn (Builder $query, $value) => $query->where('growth_cycle', '>=', $value))
                            ->when($data['max'], fn (Builder $query, $value) => $query->where('growth_cycle', '<=', $value));
                    }),
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
