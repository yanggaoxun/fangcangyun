<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BatchProgressWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 2;

    protected static ?string $heading = '最近接种批次';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Batch::query()
                    ->with(['chamber', 'strain'])
                    ->whereNull('actual_harvest_date')
                    ->orderBy('inoculation_date', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('批次号'),
                Tables\Columns\TextColumn::make('chamber.name')
                    ->label('方舱'),
                Tables\Columns\TextColumn::make('strain.type')
                    ->label('菌种')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    }),
                Tables\Columns\TextColumn::make('inoculation_date')
                    ->label('接种日期')
                    ->dateTime('Y年 n月j日'),
                Tables\Columns\TextColumn::make('expected_harvest_date')
                    ->label('预计收获')
                    ->dateTime('Y年 n月j日'),
            ])
            ->paginated(false);
    }
}
