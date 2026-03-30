<?php

namespace App\Filament\Resources\MushroomStrains;

use App\Filament\Resources\MushroomStrains\Pages\CreateMushroomStrain;
use App\Filament\Resources\MushroomStrains\Pages\EditMushroomStrain;
use App\Filament\Resources\MushroomStrains\Pages\ListMushroomStrains;
use App\Filament\Resources\MushroomStrains\Tables\MushroomStrainsTable;
use App\Models\Base;
use App\Models\MushroomStrain;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MushroomStrainResource extends Resource
{
    protected static ?string $model = MushroomStrain::class;

    protected static ?string $navigationLabel = '菌种管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    public static function getNavigationGroup(): ?string
    {
        return '菌菇管理';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // 基本信息
                TextInput::make('code')
                    ->label('菌种编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                Select::make('type')
                    ->label('菌种')
                    ->required()
                    ->options([
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($set, $state) {
                        $typeNames = [
                            'oyster' => '平菇',
                            'shiitake' => '香菇',
                            'enoki' => '金针菇',
                            'other' => '其他',
                        ];
                        $set('name', $typeNames[$state] ?? $state);
                    })
                    ->default('oyster'),

                // 成长周期和单位
                TextInput::make('growth_cycle')
                    ->label('成长周期（天）')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('天')
                    ->helperText('从接种到采收需要的天数'),

                TextInput::make('unit')
                    ->label('单位')
                    ->default('袋')
                    ->maxLength(50),

                // 环境参数
                TextInput::make('temp_min')
                    ->label('最低温度 (°C)')
                    ->numeric()
                    ->suffix('°C'),

                TextInput::make('temp_max')
                    ->label('最高温度 (°C)')
                    ->numeric()
                    ->suffix('°C'),

                TextInput::make('humidity_min')
                    ->label('最低湿度 (%)')
                    ->numeric()
                    ->suffix('%'),

                TextInput::make('humidity_max')
                    ->label('最高湿度 (%)')
                    ->numeric()
                    ->suffix('%'),

                TextInput::make('co2_min')
                    ->label('最低CO2浓度 (ppm)')
                    ->numeric()
                    ->suffix('ppm'),

                TextInput::make('co2_max')
                    ->label('最高CO2浓度 (ppm)')
                    ->numeric()
                    ->suffix('ppm'),

                TextInput::make('ph_min')
                    ->label('最低pH值')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(14),

                TextInput::make('ph_max')
                    ->label('最高pH值')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(14),

                // 基地库存配置
                Repeater::make('base_stocks')
                    ->label('基地库存配置')
                    ->helperText('为每个基地配置库存数量')
                    ->schema([
                        Select::make('base_id')
                            ->label('基地')
                            ->required()
                            ->options(fn () => Base::pluck('name', 'id'))
                            ->searchable()
                            ->distinct(), // 防止重复选择同一基地

                        TextInput::make('stock_quantity')
                            ->label('初始库存')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('单位'),
                    ])
                    ->addActionLabel('添加基地库存')
                    ->defaultItems(0)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return MushroomStrainsTable::configure($table);
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
            'index' => ListMushroomStrains::route('/'),
            'create' => CreateMushroomStrain::route('/create'),
            'edit' => EditMushroomStrain::route('/{record}/edit'),
        ];
    }
}
