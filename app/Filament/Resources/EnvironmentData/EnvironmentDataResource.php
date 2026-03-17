<?php

namespace App\Filament\Resources\EnvironmentData;

use App\Filament\Resources\EnvironmentData\Pages\CreateEnvironmentData;
use App\Filament\Resources\EnvironmentData\Pages\EditEnvironmentData;
use App\Filament\Resources\EnvironmentData\Pages\ListEnvironmentData;
use App\Filament\Resources\EnvironmentData\Schemas\EnvironmentDataForm;
use App\Filament\Resources\EnvironmentData\Tables\EnvironmentDataTable;
use App\Models\EnvironmentData;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                \Filament\Forms\Components\Select::make('chamber_id')
                    ->label('方舱')
                    ->required()
                    ->options(function () {
                        return \App\Models\Chamber::all()->pluck('name', 'id');
                    })
                    ->searchable(),

                \Filament\Forms\Components\TextInput::make('temperature')
                    ->label('温度 (°C)')
                    ->required()
                    ->numeric()
                    ->suffix('°C'),

                \Filament\Forms\Components\TextInput::make('humidity')
                    ->label('湿度 (%)')
                    ->required()
                    ->numeric()
                    ->suffix('%'),

                \Filament\Forms\Components\TextInput::make('co2_level')
                    ->label('CO2浓度 (ppm)')
                    ->required()
                    ->numeric()
                    ->suffix('ppm'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return EnvironmentDataTable::configure($table);
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
            'create' => CreateEnvironmentData::route('/create'),
            'edit' => EditEnvironmentData::route('/{record}/edit'),
        ];
    }
}
