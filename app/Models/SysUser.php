<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SysUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory\<\Database\Factories\UserFactory\> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'sys_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list\u003cstring\u003e
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'base_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list\u003cstring\u003e
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array\u003cstring, string\u003e
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 获取角色标签映射
     */
    public static function getRoleLabels(): array
    {
        return [
            'super_admin' => '超级管理员',
            'system_admin' => '系统管理员',
            'base_admin' => '基地管理员',
        ];
    }

    /**
     * 用户所属的角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SysRole::class, 'sys_user_role', 'user_id', 'role_id');
    }

    /**
     * 获取用户的所有权限
     */
    public function permissions(): array
    {
        return $this->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('name')
            ->unique()
            ->toArray();
    }

    /**
     * 检查用户是否有指定权限
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();
    }

    /**
     * 检查用户是否有指定角色
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * 为用户分配角色
     */
    public function assignRole(string|array $roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $roleIds = SysRole::whereIn('name', $roles)->pluck('id');
        $this->roles()->syncWithoutDetaching($roleIds);
    }

    /**
     * 撤销用户角色
     */
    public function removeRole(string|array $roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $roleIds = SysRole::whereIn('name', $roles)->pluck('id');
        $this->roles()->detach($roleIds);
    }

    /**
     * 同步用户角色
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = SysRole::whereIn('name', $roles)->pluck('id');
        $this->roles()->sync($roleIds);
    }

    /**
     * 检查用户是否为超级管理员
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * 检查用户是否为系统管理员
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system_admin');
    }

    /**
     * 检查用户是否为基地管理员
     */
    public function isBaseAdmin(): bool
    {
        return $this->hasRole('base_admin');
    }

    /**
     * 获取用户所属的基地
     */
    public function base(): BelongsTo
    {
        return $this->belongsTo(ChamberBase::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * 检查是否可以在资源上执行操作
     */
    public function canAccessResource(string $resource, string $action = 'view'): bool
    {
        $permission = strtolower($resource).'.'.$action;

        return $this->hasPermission($permission);
    }
}
