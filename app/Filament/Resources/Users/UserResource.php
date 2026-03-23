<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Base;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '用户管理';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $slug = 'system-users';

    public static function form(Schema $schema): Schema
    {
        $currentUser = Auth::user();

        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),

                Forms\Components\Select::make('roles')
                    ->label('角色')
                    ->relationship('roles', 'label')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->options(function () use ($currentUser) {
                        // 超级管理员可以分配所有角色
                        if ($currentUser->isSuperAdmin()) {
                            return Role::pluck('label', 'id');
                        }
                        // 系统管理员只能分配基地管理员
                        if ($currentUser->isSystemAdmin()) {
                            return Role::where('name', 'base_admin')->pluck('label', 'id');
                        }

                        return [];
                    })
                    ->live(),

                Forms\Components\Select::make('base_id')
                    ->label('所属基地')
                    ->options(Base::pluck('name', 'id'))
                    ->searchable()
                    ->visible(function ($get) {
                        $roleIds = $get('roles') ?? [];
                        if (empty($roleIds)) {
                            return false;
                        }

                        $baseAdminRole = Role::where('name', 'base_admin')->first();
                        if (! $baseAdminRole) {
                            return false;
                        }

                        return in_array($baseAdminRole->id, $roleIds);
                    })
                    ->required(function ($get) {
                        $roleIds = $get('roles') ?? [];
                        if (empty($roleIds)) {
                            return false;
                        }

                        $baseAdminRole = Role::where('name', 'base_admin')->first();
                        if (! $baseAdminRole) {
                            return false;
                        }

                        return in_array($baseAdminRole->id, $roleIds);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('roles.label')
                    ->label('角色')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($state) => $state),

                \Filament\Tables\Columns\TextColumn::make('base.name')
                    ->label('所属基地'),

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
                    ->visible(fn (Model $record) => $record->id !== Auth::id()),
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
        $user = Auth::user();

        // 基地管理员只能看到自己
        if ($user->isBaseAdmin()) {
            return parent::getEloquentQuery()->where('id', $user->id);
        }

        return parent::getEloquentQuery()->with(['roles', 'base']);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasPermission('users.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasPermission('users.create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        // 检查是否有编辑权限
        if (! $user->hasPermission('users.edit')) {
            return false;
        }

        // 超级管理员可以编辑任何人
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 自己可以编辑自己
        if ($record->id === $user->id) {
            return true;
        }

        // 系统管理员不能编辑超级管理员
        if ($user->isSystemAdmin() && $record->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        // 检查是否有删除权限
        if (! $user->hasPermission('users.delete')) {
            return false;
        }

        // 不能删除自己
        if ($record->id === $user->id) {
            return false;
        }

        // 系统管理员只能删除基地管理员
        if ($user->isSystemAdmin()) {
            return $record->isBaseAdmin();
        }

        return true;
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
