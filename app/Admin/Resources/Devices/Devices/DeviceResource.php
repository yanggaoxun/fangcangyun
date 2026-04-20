<?php

namespace App\Admin\Resources\Devices\Devices;

use App\Admin\Resources\Devices\Devices\Pages\CreateDevice;
use App\Admin\Resources\Devices\Devices\Pages\EditDevice;
use App\Admin\Resources\Devices\Devices\Pages\ListDevices;
use App\Admin\Resources\Devices\Devices\Tables\DevicesTable;
use App\Models\Chamber;
use App\Models\ChamberBase;
use App\Models\DevDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = DevDevice::class;

    protected static ?string $navigationLabel = '边缘设备';

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

                \Filament\Forms\Components\Select::make('base_id')
                    ->label('所属基地')
                    ->options(ChamberBase::where('status', 'active')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live(),

                \Filament\Forms\Components\Select::make('chamber_id')
                    ->label('所属方舱')
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $baseId = $get('base_id');
                        if (! $baseId) {
                            return [];
                        }

                        return Chamber::where('base_id', $baseId)
                            ->where('status', '!=', 'maintenance')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->placeholder('选择方舱'),

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

                \Filament\Forms\Components\TextInput::make('serial_number')
                    ->label('序列号')
                    ->maxLength(255)
                    ->nullable(),

                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('备注')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
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
