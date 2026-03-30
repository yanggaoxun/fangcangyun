<?php

namespace App\Filament\Resources\Devices;

use App\Filament\Resources\Devices\Pages\CreateDevice;
use App\Filament\Resources\Devices\Pages\EditDevice;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use App\Models\Device;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationLabel = '设备管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function getNavigationGroup(): ?string
    {
        return '设备管理';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('code')
                    ->label('设备编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                \Filament\Forms\Components\TextInput::make('name')
                    ->label('设备名称')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Select::make('chamber_id')
                    ->label('所属方舱')
                    ->options(function () {
                        return Chamber::where('status', '!=', 'maintenance')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->placeholder('选择方舱（可选）'),

                \Filament\Forms\Components\Select::make('type')
                    ->label('设备类型')
                    ->required()
                    ->options([
                        'air_conditioner' => '空调',
                        'humidifier' => '加湿器',
                        'dehumidifier' => '除湿器',
                        'ventilation' => '通风设备',
                        'led_light' => 'LED补光灯',
                        'sprinkler' => '喷淋系统',
                        'co2_generator' => 'CO2发生器',
                        'sensor' => '传感器',
                    ]),

                \Filament\Forms\Components\Select::make('status')
                    ->label('状态')
                    ->required()
                    ->options([
                        'active' => '正常',
                        'inactive' => '停用',
                        'maintenance' => '维护中',
                        'error' => '故障',
                    ])
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
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
            'index' => ListDevices::route('/'),
            'create' => CreateDevice::route('/create'),
            'edit' => EditDevice::route('/{record}/edit'),
        ];
    }
}
