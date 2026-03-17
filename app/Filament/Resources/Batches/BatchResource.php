<?php

namespace App\Filament\Resources\Batches;

use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\Schemas\BatchForm;
use App\Filament\Resources\Batches\Tables\BatchesTable;
use App\Models\Batch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Chamber;
use App\Models\MushroomStrain;

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
                // 批次基本信息
                \Filament\Forms\Components\TextInput::make('code')
                    ->label('批次编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                \Filament\Forms\Components\Select::make('chamber_id')
                    ->label('方舱')
                    ->required()
                    ->options(function () {
                        return Chamber::where('status', 'idle')->orWhere('status', 'planting')->pluck('name', 'id');
                    })
                    ->searchable(),

                \Filament\Forms\Components\Select::make('strain_id')
                    ->label('菌种')
                    ->required()
                    ->options(function () {
                        return MushroomStrain::where('is_active', true)->where('stock_quantity', '>', 0)->pluck('name', 'id');
                    })
                    ->searchable(),

                \Filament\Forms\Components\DatePicker::make('inoculation_date')
                    ->label('接种日期')
                    ->required()
                    ->default(now())
                    ->native(false),

                \Filament\Forms\Components\DatePicker::make('expected_harvest_date')
                    ->label('预计采收日期')
                    ->native(false),

                \Filament\Forms\Components\Select::make('stage')
                    ->label('生长阶段')
                    ->required()
                    ->options([
                        'spawning' => '发菌期',
                        'pinning' => '原基期',
                        'fruiting' => '生长期',
                        'harvested' => '采收期',
                    ])
                    ->default('spawning'),

                \Filament\Forms\Components\Select::make('status')
                    ->label('批次状态')
                    ->required()
                    ->options([
                        'active' => '活跃',
                        'completed' => '已完成',
                        'failed' => '失败',
                    ])
                    ->default('active'),

                \Filament\Forms\Components\TextInput::make('substrate_quantity')
                    ->label('培养料数量')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                \Filament\Forms\Components\TextInput::make('expected_yield')
                    ->label('预计产量 (kg)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->step(0.01),

                \Filament\Forms\Components\TextInput::make('actual_yield')
                    ->label('实际产量 (kg)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->step(0.01),

                \Filament\Forms\Components\DatePicker::make('actual_harvest_date')
                    ->label('实际采收日期')
                    ->native(false),

                \Filament\Forms\Components\Textarea::make('notes')
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
