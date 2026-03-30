<?php

namespace App\Filament\Resources\Bases;

use App\Filament\Resources\Bases\Pages\ManageBases;
use App\Models\Base;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BaseResource extends Resource
{
    protected static ?string $model = Base::class;

    protected static ?string $navigationLabel = '基地管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')
                    ->label('基地编号')
                    ->required()
                    ->maxLength(50)
                    ->unique(),
                TextInput::make('name')
                    ->label('基地名称')
                    ->required()
                    ->maxLength(100),
                TextInput::make('location')
                    ->label('基地位置')
                    ->required()
                    ->maxLength(200),
                TextInput::make('manager')
                    ->label('负责人')
                    ->maxLength(50),
                TextInput::make('phone')
                    ->label('联系电话')
                    ->tel()
                    ->maxLength(20),
                Textarea::make('description')
                    ->label('基地描述')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '正常运营',
                        'inactive' => '暂停使用',
                    ])
                    ->default('active')
                    ->required()
                    ->live()
                    ->rules([
                        fn ($record) => function ($attribute, $value, $fail) use ($record) {
                            if (! $record) {
                                return;
                            }

                            // 检查是否从 active 改为 inactive
                            if ($record->status === 'active' && $value === 'inactive') {
                                $plantingChambers = $record->chambers()
                                    ->where('status', 'planting')
                                    ->count();

                                if ($plantingChambers > 0) {
                                    $fail("该基地下有 {$plantingChambers} 个方舱处于种植中状态，无法修改为暂停使用");
                                }
                            }
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('基地编号')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('基地名称')
                    ->searchable(),
                TextColumn::make('location')
                    ->label('基地位置')
                    ->searchable(),
                TextColumn::make('manager')
                    ->label('负责人'),
                TextColumn::make('phone')
                    ->label('联系电话'),
                BadgeColumn::make('status')
                    ->label('状态')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => '正常运营',
                        'inactive' => '暂停使用',
                    }),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '正常运营',
                        'inactive' => '暂停使用',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('编辑'),
                DeleteAction::make()
                    ->label('删除'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('删除选中'),
                ])
                    ->label('批量操作'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBases::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return '基地管理';
    }

    public static function getNavigationGroup(): ?string
    {
        return '方舱管理';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
