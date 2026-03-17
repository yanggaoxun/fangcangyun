<?php

namespace App\Filament\Resources\Chambers;

use App\Filament\Resources\Chambers\Pages\CreateChamber;
use App\Filament\Resources\Chambers\Pages\EditChamber;
use App\Filament\Resources\Chambers\Pages\ListChambers;
use App\Filament\Resources\Chambers\Schemas\ChamberForm;
use App\Filament\Resources\Chambers\Tables\ChambersTable;
use App\Models\Chamber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChamberResource extends Resource
{
    protected static ?string $model = Chamber::class;

    protected static ?string $navigationLabel = '方舱管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return '方舱管理';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // 基本信息
                \Filament\Forms\Components\TextInput::make('code')
                    ->label('方舱编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                \Filament\Forms\Components\TextInput::make('name')
                    ->label('方舱名称')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('location')
                    ->label('位置')
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('capacity')
                    ->label('容量')
                    ->numeric()
                    ->required()
                    ->suffix('袋'),

                \Filament\Forms\Components\Select::make('type')
                    ->label('菌种类型')
                    ->required()
                    ->options([
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    ])
                    ->default('oyster'),

                \Filament\Forms\Components\Select::make('status')
                    ->label('状态')
                    ->required()
                    ->options([
                        'idle' => '空闲',
                        'planting' => '种植中',
                        'maintenance' => '维护中',
                    ])
                    ->default('idle'),

                \Filament\Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                // 环境参数
                \Filament\Forms\Components\TextInput::make('target_temperature')
                    ->label('目标温度')
                    ->numeric()
                    ->suffix(' °C')
                    ->default(25),

                \Filament\Forms\Components\TextInput::make('target_humidity')
                    ->label('目标湿度')
                    ->numeric()
                    ->suffix('%')
                    ->default(80),

                \Filament\Forms\Components\TextInput::make('target_co2')
                    ->label('目标CO2浓度')
                    ->numeric()
                    ->suffix('ppm')
                    ->default(1000),

                \Filament\Forms\Components\TextInput::make('target_ph')
                    ->label('目标pH值')
                    ->numeric()
                    ->step(0.1)
                    ->default(6.5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ChambersTable::configure($table);
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
            'index' => ListChambers::route('/'),
            'create' => CreateChamber::route('/create'),
            'edit' => EditChamber::route('/{record}/edit'),
        ];
    }
}
