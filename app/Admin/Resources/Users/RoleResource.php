<?php

namespace App\Admin\Resources\Users;

use App\Admin\Resources\Users\Pages\CreateRole;
use App\Admin\Resources\Users\Pages\EditRole;
use App\Admin\Resources\Users\Pages\ListRoles;
use App\Models\Permission;
use App\Models\Role;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationLabel = '角色管理';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $slug = 'system-roles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('角色标识')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),

                Forms\Components\TextInput::make('label')
                    ->label('角色名称')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('角色描述')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\CheckboxList::make('permissions')
                    ->label('权限分配')
                    ->helperText('为该角色分配权限')
                    ->relationship('permissions', 'label')
                    ->options(function () {
                        return Permission::all()->pluck('label', 'id')->toArray();
                    })
                    ->columns(2)
                    ->gridDirection('row')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('角色标识')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('label')
                    ->label('角色名称')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->limit(50),

                \Filament\Tables\Columns\TextColumn::make('permissions_count')
                    ->label('权限数量')
                    ->counts('permissions'),

                \Filament\Tables\Columns\TextColumn::make('users_count')
                    ->label('用户数量')
                    ->counts('users'),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->label('编辑'),
                \Filament\Actions\DeleteAction::make()
                    ->label('删除')
                    ->visible(fn (Model $record) => ! in_array($record->name, ['super_admin', 'system_admin', 'base_admin'])),
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
            ->withCount(['permissions', 'users']);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasPermission('roles.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasPermission('roles.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasPermission('roles.edit');
    }

    public static function canDelete(Model $record): bool
    {
        // 系统内置角色不能删除
        if (in_array($record->name, ['super_admin', 'system_admin', 'base_admin'])) {
            return false;
        }

        return Auth::user()->hasPermission('roles.delete');
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
