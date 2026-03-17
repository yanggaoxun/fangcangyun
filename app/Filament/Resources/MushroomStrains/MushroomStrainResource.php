<?php

namespace App\Filament\Resources\MushroomStrains;

use App\Filament\Resources\MushroomStrains\Pages\CreateMushroomStrain;
use App\Filament\Resources\MushroomStrains\Pages\EditMushroomStrain;
use App\Filament\Resources\MushroomStrains\Pages\ListMushroomStrains;
use App\Filament\Resources\MushroomStrains\Schemas\MushroomStrainForm;
use App\Filament\Resources\MushroomStrains\Tables\MushroomStrainsTable;
use App\Models\MushroomStrain;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MushroomStrainResource extends Resource
{
    protected static ?string $model = MushroomStrain::class;

    protected static ?string $navigationLabel = '菌种管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return '菌菇管理';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('code')
                    ->label('菌种编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                \Filament\Forms\Components\TextInput::make('name')
                    ->label('菌种名称')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Select::make('type')
                    ->label('菌种类别')
                    ->required()
                    ->options([
                        'oyster' => '平菇',
                        'shiitake' => '香菇',
                        'enoki' => '金针菇',
                        'other' => '其他',
                    ])
                    ->default('oyster'),
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
