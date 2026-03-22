<?php

namespace App\Filament\Resources\Batches;

use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\Tables\BatchesTable;
use App\Models\Base;
use App\Models\Batch;
use App\Models\Chamber;
use App\Models\MushroomStrain;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationLabel = '种植批次';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return '菌菇管理';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // 选择基地（编辑时显示为只读文本）
                Forms\Components\TextInput::make('base_name')
                    ->label('基地')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit')
                    ->formatStateUsing(function ($record) {
                        return $record?->chamber?->base?->name ?? '';
                    }),

                // 选择基地（创建时使用）
                Forms\Components\Select::make('base_id')
                    ->label('基地')
                    ->required()
                    ->options(function () {
                        return Base::pluck('name', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->hiddenOn('edit')
                    ->afterStateUpdated(fn ($set) => [
                        $set('chamber_id', null),
                        $set('strain_id', null),
                        $set('code', null),
                    ]),

                // 选择方舱（编辑时显示为只读文本）
                Forms\Components\TextInput::make('chamber_name')
                    ->label('方舱')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit')
                    ->formatStateUsing(function ($record) {
                        return $record?->chamber?->name ?? '';
                    }),

                // 选择方舱（创建时使用）
                Forms\Components\Select::make('chamber_id')
                    ->label('方舱')
                    ->required()
                    ->options(function ($get) {
                        $baseId = $get('base_id');
                        if (! $baseId) {
                            return [];
                        }

                        return Chamber::where('base_id', $baseId)
                            ->where('status', 'idle')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->hiddenOn('edit')
                    ->afterStateUpdated(function ($set, $get) {
                        $baseId = $get('base_id');
                        $chamberId = $get('chamber_id');

                        if ($baseId && $chamberId) {
                            $base = Base::find($baseId);
                            $chamber = Chamber::find($chamberId);

                            if ($base && $chamber) {
                                $batchCode = $base->code.$chamber->code.now()->format('Ymd');
                                $set('code', $batchCode);
                            }
                        }
                    }),

                // 批次编号（自动生成，创建页面隐藏，编辑页面显示）
                Forms\Components\TextInput::make('code')
                    ->label('批次编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->disabled()
                    ->dehydrated()
                    ->hiddenOn('create'),

                // 选择菌种 - 根据基地筛选有库存的菌种
                Forms\Components\Select::make('strain_id')
                    ->label('菌种')
                    ->required()
                    ->options(function ($get) {
                        $baseId = $get('base_id');
                        if (! $baseId) {
                            return [];
                        }

                        // 只显示该基地有库存的活跃菌种
                        return MushroomStrain::where('is_active', true)
                            ->whereHas('baseStocks', function ($query) use ($baseId) {
                                $query->where('base_id', $baseId)
                                    ->whereRaw('stock_quantity - reserved_quantity > 0');
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($set, $get, $state) {
                        if ($state) {
                            $strain = MushroomStrain::find($state);
                            if ($strain && $strain->growth_cycle) {
                                $inoculationDate = $get('inoculation_date');
                                if ($inoculationDate) {
                                    $expectedDate = \Carbon\Carbon::parse($inoculationDate)->addDays($strain->growth_cycle);
                                    $set('expected_harvest_date', $expectedDate);
                                }
                            }
                        }
                    }),

                // 菌类数量（扣减库存）- 显示基地特定库存
                Forms\Components\TextInput::make('strain_quantity')
                    ->label('菌类数量')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->live()
                    ->rules(['required', 'integer', 'min:1'])
                    ->hint(function ($get) {
                        $strainId = $get('strain_id');
                        $baseId = $get('base_id');

                        if ($strainId && $baseId) {
                            $strain = MushroomStrain::find($strainId);
                            $base = Base::find($baseId);

                            if ($strain && $base) {
                                $stock = $base->getStrainStock($strainId);
                                $available = $stock ? $stock->available_quantity : 0;

                                return "{$base->name}基地库存: {$available} {$strain->unit}";
                            }
                        }

                        return null;
                    }),

                // 接种日期时间（编辑时只读）
                Forms\Components\DateTimePicker::make('inoculation_date')
                    ->label('接种日期时间')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->disabledOn('edit')
                    ->live()
                    ->format('Y年 n月j日 H:i')
                    ->afterStateUpdated(function ($set, $get, $state) {
                        if ($state) {
                            $strainId = $get('strain_id');
                            if ($strainId) {
                                $strain = MushroomStrain::find($strainId);
                                if ($strain && $strain->growth_cycle) {
                                    $expectedDate = \Carbon\Carbon::parse($state)->addDays($strain->growth_cycle);
                                    $set('expected_harvest_date', $expectedDate);
                                }
                            }
                        }
                    }),

                // 预计采收日期时间
                Forms\Components\DateTimePicker::make('expected_harvest_date')
                    ->label('预计采收日期时间')
                    ->native(false)
                    ->format('Y年 n月j日 H:i')
                    ->helperText('根据菌种成长周期自动计算，也可手动调整'),

                // 实际采收日期时间
                Forms\Components\DateTimePicker::make('actual_harvest_date')
                    ->label('实际采收日期时间')
                    ->native(false)
                    ->format('Y年 n月j日 H:i')
                    ->default(fn () => now())
                    ->hiddenOn('create'),

                // 预计产量
                Forms\Components\TextInput::make('expected_yield')
                    ->label('预计产量 (kg)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->step(1),

                // 实际产量
                Forms\Components\TextInput::make('actual_yield')
                    ->label('实际产量 (kg)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->step(0.01)
                    ->hiddenOn('create'),

                // 备注
                Forms\Components\Textarea::make('notes')
                    ->label('备注')
                    ->columnSpanFull()
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return BatchesTable::configure($table);
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
            'index' => ListBatches::route('/'),
            'create' => CreateBatch::route('/create'),
            'edit' => EditBatch::route('/{record}/edit'),
        ];
    }
}
