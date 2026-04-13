<?php

namespace App\Admin\Resources\Mushroom\Batches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                TextColumn::make('strain.type')
                    ->label('菌种')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    })
                    ->searchable(),

                TextColumn::make('strain_quantity')
                    ->label('菌类数量')
                    ->suffix(fn ($record) => ' '.$record->strain->unit)
                    ->sortable(),

                TextColumn::make('inoculation_date')
                    ->label('接种时间')
                    ->dateTime('Y年 n月j日 H:i')
                    ->sortable(),

                TextColumn::make('expected_harvest_date')
                    ->label('预计采收时间')
                    ->dateTime('Y年 n月j日 H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('actual_harvest_date')
                    ->label('实际采收时间')
                    ->dateTime('Y年 n月j日 H:i')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('harvest_status')
                    ->label('收获状态')
                    ->icon(fn ($record) => $record->actual_harvest_date ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->color(fn ($record) => $record->actual_harvest_date ? 'success' : 'warning')
                    ->getStateUsing(fn ($record) => $record->actual_harvest_date ? '已收获' : '种植中'),

                TextColumn::make('expected_yield')
                    ->label('预计产量')
                    ->suffix(' kg')
                    ->toggleable(),

                TextColumn::make('actual_yield')
                    ->label('实际产量')
                    ->suffix(' kg')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('base')
                    ->label('基地')
                    ->relationship('chamber.base', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('chamber')
                    ->label('方舱')
                    ->relationship('chamber', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('strain_id')
                    ->label('菌种')
                    ->relationship('strain', 'type')
                    ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->type) {
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                        default => $record->type,
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('harvest_status')
                    ->label('收获状态')
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'harvested') {
                            $query->whereNotNull('actual_harvest_date');
                        } elseif ($data['value'] === 'planting') {
                            $query->whereNull('actual_harvest_date');
                        }
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('value')
                            ->label('状态')
                            ->options([
                                'planting' => '种植中',
                                'harvested' => '已收获',
                            ])
                            ->placeholder('全部'),
                    ]),

                Filter::make('inoculation_date')
                    ->label('接种日期')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('开始日期'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('inoculation_date', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date) => $query->whereDate('inoculation_date', '<=', $date));
                    }),

                Filter::make('expected_harvest_date')
                    ->label('预计采收日期')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('开始日期'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('expected_harvest_date', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date) => $query->whereDate('expected_harvest_date', '<=', $date));
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
