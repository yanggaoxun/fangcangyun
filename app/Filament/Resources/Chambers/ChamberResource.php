<?php

namespace App\Filament\Resources\Chambers;

use App\Filament\Resources\Chambers\Pages\CreateChamber;
use App\Filament\Resources\Chambers\Pages\EditChamber;
use App\Filament\Resources\Chambers\Pages\ListChambers;
use App\Filament\Resources\Chambers\Tables\ChambersTable;
use App\Models\Base;
use App\Models\Chamber;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ChamberResource extends Resource
{
    protected static ?string $model = Chamber::class;

    protected static ?string $navigationLabel = '方舱列表';

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
                Forms\Components\Select::make('base_id')
                    ->label('所属基地')
                    ->options(function () {
                        return Base::where('status', 'active')->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('code')
                    ->label('方舱编号')
                    ->required()
                    ->maxLength(50)
                    ->rules([
                        function ($record) {
                            return Rule::unique('chambers', 'code')
                                ->where('base_id', fn ($input) => $input['base_id'])
                                ->ignore($record?->id);
                        },
                    ])
                    ->validationMessages([
                        'unique' => '该基地已存在相同编号的方舱',
                    ]),

                Forms\Components\TextInput::make('name')
                    ->label('方舱名称')
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        function ($record) {
                            return Rule::unique('chambers', 'name')
                                ->where('base_id', fn ($input) => $input['base_id'])
                                ->ignore($record?->id);
                        },
                    ])
                    ->validationMessages([
                        'unique' => '该基地已存在相同名称的方舱',
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
            'index' => ListChambers::route('/'),
            'create' => CreateChamber::route('/create'),
            'edit' => EditChamber::route('/{record}/edit'),
        ];
    }
}
