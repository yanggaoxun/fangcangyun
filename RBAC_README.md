# RBAC 权限系统实现文档

## 系统架构

基于角色的访问控制（RBAC）系统已实现，包含以下组件：

### 1. 数据库表结构

- **permissions** - 权限表
  - `name`: 权限标识 (如: users.view, users.create)
  - `label`: 权限名称 (如: 查看用户)
  - `group`: 权限分组 (如: 用户管理)
  - `description`: 权限描述

- **roles** - 角色表
  - `name`: 角色标识 (如: super_admin)
  - `label`: 角色名称 (如: 超级管理员)
  - `description`: 角色描述

- **permission_role** - 角色-权限关联表
- **role_user** - 用户-角色关联表

### 2. 模型关系

```php
User -> roles() -> Role
Role -> permissions() -> Permission
Role -> users() -> User
Permission -> roles() -> Role
```

### 3. 预定义权限列表

系统已预定义以下权限：

#### 用户管理
- users.view - 查看用户
- users.create - 创建用户
- users.edit - 编辑用户
- users.delete - 删除用户

#### 角色管理
- roles.view - 查看角色
- roles.create - 创建角色
- roles.edit - 编辑角色
- roles.delete - 删除角色

#### 权限管理
- permissions.view - 查看权限
- permissions.create - 创建权限
- permissions.edit - 编辑权限
- permissions.delete - 删除权限

#### 基地管理
- bases.view, bases.create, bases.edit, bases.delete

#### 方舱管理
- chambers.view, chambers.create, chambers.edit, chambers.delete

#### 菌种管理
- strains.view, strains.create, strains.edit, strains.delete

#### 批次管理
- batches.view, batches.create, batches.edit, batches.delete

#### 设备管理
- devices.view, devices.create, devices.edit, devices.delete

#### 环境监控
- environment.view, environment.create, environment.edit, environment.delete

#### 报警管理
- alerts.view, alerts.create, alerts.edit, alerts.delete

### 4. 预定义角色

系统已预定义三个角色：

1. **超级管理员 (super_admin)**
   - 拥有所有权限
   - 可以管理角色和权限
   - 可以编辑任何用户

2. **系统管理员 (system_admin)**
   - 拥有除角色/权限管理外的所有权限
   - 只能分配基地管理员角色
   - 不能编辑超级管理员

3. **基地管理员 (base_admin)**
   - 只能查看和管理所属基地的数据
   - 可以查看和编辑方舱、批次等
   - 不能管理用户和角色

## 使用方法

### 1. 初始化系统

```bash
# 运行迁移
php artisan migrate

# 初始化权限和角色数据
php artisan db:seed --class=RbacSeeder
```

### 2. 为用户分配角色

```php
$user = User::find(1);
$user->assignRole('super_admin');

// 或同步多个角色
$user->syncRoles(['base_admin', 'editor']);

// 撤销角色
$user->removeRole('base_admin');
```

### 3. 检查权限

```php
// 检查是否有某个权限
if ($user->hasPermission('users.create')) {
    // 可以创建用户
}

// 检查是否有某个角色
if ($user->hasRole('super_admin')) {
    // 是超级管理员
}

// 检查是否为特定角色类型
if ($user->isSuperAdmin()) {
    // 是超级管理员
}
```

### 4. 在Resource中使用权限控制

```php
public static function canCreate(): bool
{
    return Auth::user()->hasPermission('users.create');
}

public static function canEdit(Model $record): bool
{
    return Auth::user()->hasPermission('users.edit');
}
```

### 5. 在表单中使用权限控制

```php
// 只有有权限的用户才能看到某些字段
Forms\Components\TextInput::make('admin_field')
    ->visible(fn () => Auth::user()->hasPermission('admin.access'));
```

## 管理界面

### 角色管理
- 路径: /admin/roles
- 功能: 创建、编辑、删除角色，分配权限
- 权限要求: roles.view, roles.create, roles.edit, roles.delete

### 用户管理
- 路径: /admin/users
- 功能: 创建、编辑用户，分配角色
- 权限要求: users.view, users.create, users.edit, users.delete

## 扩展权限

### 添加新权限

```php
Permission::create([
    'name' => 'reports.view',
    'label' => '查看报表',
    'group' => '报表管理',
    'description' => '允许查看系统报表'
]);
```

### 给角色分配新权限

```php
$role = Role::where('name', 'system_admin')->first();
$role->givePermissionTo('reports.view');

// 或同步多个权限
$role->syncPermissions(['reports.view', 'reports.export']);

// 撤销权限
$role->revokePermissionTo('reports.view');
```

## 注意事项

1. **超级管理员角色** (`super_admin`) 是系统内置角色，不可删除
2. **系统管理员** 不能管理角色和权限，只能分配基地管理员
3. **基地管理员** 只能看到和管理所属基地的数据
4. 所有权限检查都应通过 `hasPermission()` 方法，而不是直接检查角色
5. 界面元素的显示/隐藏也应基于权限，而不仅仅是角色

## 数据库迁移

迁移文件位置:
- `database/migrations/2026_03_21_000737_create_roles_and_permissions_tables.php`

运行命令:
```bash
php artisan migrate
php artisan db:seed --class=RbacSeeder
```
