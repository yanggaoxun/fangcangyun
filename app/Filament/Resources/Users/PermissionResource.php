<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreatePermission;
use App\Filament\Resources\Users\Pages\EditPermission;
use App\Filament\Resources\Users\Pages\ListPermissions;
use App\Models\Permission;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationLabel = '权限管理';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('权限标识')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('例如: users.view')
                    ->disabledOn('edit'),

                Forms\Components\TextInput::make('label')
                    ->label('权限名称')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('例如: 查看用户'),

                Forms\Components\TextInput::make('group')
                    ->label('权限分组')
                    ->maxLength(255)
                    ->placeholder('例如: 用户管理')
                    ->default('其他'),

                Forms\Components\Textarea::make('description')
                    ->label('权限描述')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('权限标识')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('label')
                    ->label('权限名称')
                    ->searchable(),

                \Filament\Tables\Columns\BadgeColumn::make('group')
                    ->label('分组')
                    ->color('primary'),

                \Filament\Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->limit(50)
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('group')
                    ->label('分组')
                    ->options(function () {
                        return Permission::distinct('group')->pluck('group', 'group');
                    }),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->label('编辑'),
                \Filament\Actions\DeleteAction::make()
                    ->label('删除'),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('删除选中'),
                ])
                    ->label('批量操作'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('group')
            ->orderBy('name');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasPermission('permissions.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasPermission('permissions.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasPermission('permissions.edit');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->hasPermission('permissions.delete');
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
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }
}
