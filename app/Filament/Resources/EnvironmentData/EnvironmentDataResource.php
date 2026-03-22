<?php

namespace App\Filament\Resources\EnvironmentData;

use App\Filament\Resources\EnvironmentData\Pages\ListEnvironmentData;
use App\Filament\Resources\EnvironmentData\Pages\ViewEnvironmentData;
use App\Models\EnvironmentData;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnvironmentDataResource extends Resource
{
    protected static ?string $model = EnvironmentData::class;

    protected static ?string $navigationLabel = '环境数据';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return '环境监控';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('chamber_id')
                    ->label('方舱')
                    ->required()
                    ->options(function () {
                        return \App\Models\Chamber::all()->pluck('name', 'id');
                    })
                    ->searchable(),

                TextInput::make('temperature')
                    ->label('温度 (°C)')
                    ->required()
                    ->numeric()
                    ->suffix('°C'),

                TextInput::make('humidity')
                    ->label('湿度 (%)')
                    ->required()
                    ->numeric()
                    ->suffix('%'),

                TextInput::make('co2_level')
                    ->label('CO2浓度 (ppm)')
                    ->required()
                    ->numeric()
                    ->suffix('ppm'),
            ]);
    }

    public static function table(Table $table): Table
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnvironmentData::route('/'),
            'view' => ViewEnvironmentData::route('/{record}'),
        ];
    }
}
