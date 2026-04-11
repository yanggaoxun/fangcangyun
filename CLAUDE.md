# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## UI/UX 规范

### 保存操作通知规范
所有保存操作必须使用 Filament 原生通知系统，确保用户体验一致：

**成功提示：**
```javascript
new FilamentNotification()
    .title('操作成功')
    .success()
    .body('配置已保存')
    .send();
```

**失败提示：**
```javascript
new FilamentNotification()
    .title('操作失败')
    .danger()
    .body(errorMessage)
    .send();
```

**注意事项：**
- 使用 `FilamentNotification` 类（挂载在 window 对象上）
- 成功使用 `.success()` 方法，失败使用 `.danger()` 方法
- 标题和正文都要提供，确保信息完整
- 通知会自动显示在页面右上角，与手动控制页面的提示风格一致

## Project Overview

This is a Laravel 12 application with Filament 4.0 admin panel, containerized with Docker for managing mushroom cultivation chambers automation.

## Key Commands

### Development
```bash
# Start all services (app, web, db) in Docker
docker-compose up -d

# Run development server with hot reload, queue worker, and log monitoring
composer run dev

# Run tests
composer run test
# or
php artisan test

# Run a single test
php artisan test --filter TestName

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Create a new migration
php artisan make:migration create_table_name

# Access MySQL console
docker exec -it laravel_db mysql -uroot -padmin123 laravel
```

### Filament Admin Panel
```bash
# Create a new Filament resource
php artisan make:filament-resource ResourceName

# Create a new Filament page
php artisan make:filament-page PageName

# Upgrade Filament after updates
php artisan filament:upgrade
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check code formatting without changes
./vendor/bin/pint --test
```

## Architecture Overview

### Service Architecture
- **App Container**: PHP 8.2 with Laravel 12, runs on port 9000 internally
- **Web Container**: Nginx reverse proxy, accessible at localhost:8084
- **DB Container**: MySQL 8.0, accessible at localhost:3307

### Key Directories
- `app/Admin/`: Filament admin resources, pages, and widgets
- `app/Models/`: Eloquent models
- `database/migrations/`: Database migrations
- `config/`: Configuration files
- `routes/`: Application routes (web.php for Filament routes)

### Filament Integration
The application uses Filament as the admin panel framework:
- Admin panel accessible at `/admin`
- Panel provider: `app/Providers/Filament/AdminPanelProvider.php`
- Resources auto-discovered from `app/Admin/Resources`
- Default authentication with User model implementing `FilamentUser` interface

### Database Configuration
- MySQL connection configured for Docker service 'db'
- Database name: `laravel`
- Credentials in `.env`: DB_USERNAME=root, DB_PASSWORD=admin123
- Session, cache, and queue use database driver by default

### Development Workflow
1. The application runs in Docker containers for consistency
2. Source code is volume-mounted for live editing
3. Composer scripts handle common development tasks
4. Filament provides rapid admin interface development
5. All services communicate through Docker network 'laravel'