<?php

namespace App\Admin\Resources\Chambers\Chambers;

use App\Admin\Resources\Chambers\Chambers\Pages\ChamberMonitoring;
use App\Admin\Resources\Chambers\Chambers\Pages\CreateChamber;
use App\Admin\Resources\Chambers\Chambers\Pages\EditChamber;
use App\Admin\Resources\Chambers\Chambers\Pages\ListChambers;
use App\Admin\Resources\Chambers\Chambers\Tables\ChambersTable;
use App\Models\Chamber;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ChamberResource extends Resource
{
    protected static ?string $model = Chamber::class;

    protected static ?string $slug = 'chambers';

    protected static ?string $navigationLabel = '方舱管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = '方舱管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // 基本信息
                Forms\Components\Select::make('base_id')
                    ->label('所属基地')
                    ->options(function () {
                        return ChamberBase::where('status', 'active')->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('code')
                    ->label('方舱编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(
                        table: 'chambers',
                        column: 'code',
                        ignoreRecord: true,
                    )
                    ->validationMessages([
                        'unique' => '该方舱编号已存在',
                    ]),

                Forms\Components\TextInput::make('device_code')
                    ->label('边缘设备编码')
                    ->helperText('用于远程控制设备关联和数据传输识别')
                    ->maxLength(100)
                    ->unique(
                        table: 'chambers',
                        column: 'device_code',
                        ignoreRecord: true,
                    )
                    ->validationMessages([
                        'unique' => '该设备编码已被使用',
                    ]),

                Forms\Components\TextInput::make('name')
                    ->label('方舱名称')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'chambers',
                        column: 'name',
                        ignoreRecord: true,
                    )
                    ->validationMessages([
                        'unique' => '该方舱名称已存在',
                    ]),

                Forms\Components\TextInput::make('capacity')
                    ->label('容量')
                    ->numeric()
                    ->required()
                    ->suffix('袋'),

                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->required()
                    ->options([
                        'idle' => '空闲',
                        'planting' => '种植中',
                        'maintenance' => '维护中',
                    ])
                    ->default('idle'),

                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->maxLength(65535)
                    ->columnSpanFull(),
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
            'index' => ListChambers::route('/chambers'),
            'create' => CreateChamber::route('/create'),
            'edit' => EditChamber::route('/{record}/edit'),
            'monitor' => ChamberMonitoring::route('/monitor'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make('方舱列表')
                ->icon('heroicon-o-rectangle-stack')
                ->url(static::getUrl())
                ->sort(2)
                ->group('方舱管理')
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.chambers.chambers')),
            \Filament\Navigation\NavigationItem::make('方舱监控')
                ->icon('heroicon-o-chart-bar')
                ->url(static::getUrl('monitor'))
                ->sort(3)
                ->group('方舱管理')
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.chambers.monitor')),
        ];
    }
}
