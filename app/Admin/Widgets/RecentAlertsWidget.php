<?php

namespace App\Admin\Widgets;

use App\Models\SysAlert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = '最新报警';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SysAlert::query()
                    ->with('chamber')
                    ->where('is_acknowledged', false)
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('m-d H:i')
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('chamber.name')
                    ->label('方舱')
                    ->color('gray')
                    ->size('sm')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'critical' => '严重',
                        'warning' => '警告',
                        'info' => '信息',
                        default => $state,
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'critical' => 'heroicon-m-exclamation-circle',
                        'warning' => 'heroicon-m-exclamation-triangle',
                        'info' => 'heroicon-m-information-circle',
                        default => 'heroicon-m-flag',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->limit(25)
                    ->tooltip(fn ($state): string => $state)
                    ->weight('font-medium'),
            ])
            ->paginated(false)
            ->striped();
    }
}
