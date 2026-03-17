<?php

namespace App\Filament\Resources\Alerts;

use App\Filament\Resources\Alerts\Pages\CreateAlert;
use App\Filament\Resources\Alerts\Pages\EditAlert;
use App\Filament\Resources\Alerts\Pages\ListAlerts;
use App\Filament\Resources\Alerts\Schemas\AlertForm;
use App\Filament\Resources\Alerts\Tables\AlertsTable;
use App\Models\Alert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationLabel = '报警管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return '系统管理';
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

                \Filament\Forms\Components\Select::make('type')
                    ->label('报警类型')
                    ->required()
                    ->options([
                        'temperature' => '温度异常',
                        'humidity' => '湿度异常',
                        'co2' => 'CO2异常',
                        'ph' => 'pH异常',
                        'device_error' => '设备故障',
                        'stock_low' => '库存不足',
                        'growth_anomaly' => '生长异常',
                    ]),

                \Filament\Forms\Components\Select::make('level')
                    ->label('报警级别')
                    ->required()
                    ->options([
                        'info' => '信息',
                        'warning' => '警告',
                        'critical' => '严重',
                    ])
                    ->default('warning'),

                \Filament\Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Textarea::make('message')
                    ->label('报警消息')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return AlertsTable::configure($table);
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
            'index' => ListAlerts::route('/'),
            'create' => CreateAlert::route('/create'),
            'edit' => EditAlert::route('/{record}/edit'),
        ];
    }
}
