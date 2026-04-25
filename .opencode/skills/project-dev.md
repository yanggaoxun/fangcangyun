# 蘑菇方舱自动化系统 - 项目开发规范

## 项目概述

基于 Laravel 12 + Filament 4.0 的菌菇类方舱自动化后台管理系统，通过 MQTT 与边缘设备（树莓派）通信，实现环境监控、设备控制和自动化管理。

## 技术栈

- **后端**：Laravel 12 (PHP 8.2+)
- **管理面板**：Filament 4.0
- **数据库**：MySQL 8.0
- **消息队列**：Laravel Queue (database driver)
- **MQTT Broker**：EMQX 5.8.6
- **容器化**：Docker + Docker Compose
- **前端**：Alpine.js + Tailwind CSS (Filament 内置)

## 代码规范

### PHP / Laravel

1. **命名规范**
   - 类名：PascalCase (如 `ChamberControlConfig`)
   - 方法名：camelCase (如 `processAutoControl`)
   - 变量名：snake_case (如 `$chamber_id`)
   - 常量：UPPER_SNAKE_CASE

2. **命名空间**
   ```
   App\Admin\Resources\Chambers\Chambers   // Filament 资源
   App\Http\Controllers\Api               // API 控制器
   App\Models                             // 模型
   App\Services                           // 业务服务
   App\Jobs                               // 队列任务
   App\Octane\Processes                   // Octane 进程
   ```

3. **类型声明**
   - 所有方法参数和返回值必须声明类型
   - 使用 `?int`、`?string` 表示可空类型
   - 使用 `array` 或具体类型如 `array<string, mixed>`

### Filament 4.0

1. **资源组织**
   ```
   app/Admin/Resources/
   ├── Chambers/
   │   ├── Bases/          // 基地管理
   │   ├── Chambers/       // 方舱管理
   │   │   ├── Pages/      // 页面
   │   │   ├── Tables/     // 表格
   │   │   └── ChamberResource.php
   │   └── ...
   ├── Devices/            // 设备管理
   ├── Mushroom/           // 菌菇管理
   └── System/             // 系统管理
   ```

2. **表单组件**
   - 使用 `Forms\Components` 命名空间（Filament 4.0）
   - 表单闭包参数使用 `\Filament\Schemas\Components\Utilities\Get`
   - 验证消息使用中文

3. **通知规范**
   ```php
   // 成功
   new FilamentNotification()
       ->title('操作成功')
       ->success()
       ->body('配置已保存')
       ->send();
   
   // 失败
   new FilamentNotification()
       ->title('操作失败')
       ->danger()
       ->body($errorMessage)
       ->send();
   ```

## 数据库规范

### 表命名

采用 **模块前缀** 命名法：

| 模块 | 前缀 | 示例 |
|------|------|------|
| 方舱管理 | `chambers_` | `chambers_bases`, `chambers_chambers` |
| 菌菇管理 | `mush_` | `mush_strains`, `mush_batches` |
| 设备管理 | `dev_` | `dev_devices`, `dev_controls` |
| 系统管理 | `sys_` | `sys_users`, `sys_roles` |

### 字段命名

- 主键：`id` (bigInt, autoIncrement)
- 外键：`{table}_id` (如 `chamber_id`, `base_id`)
- 时间戳：`created_at`, `updated_at`
- 软删除：`deleted_at`（如需要）
- 布尔值：`is_{形容词}` (如 `is_enabled`, `is_active`)
- JSON 字段：`{name}_config` 或 `{name}_data`

### 迁移规范

1. **创建表迁移**
   ```php
   Schema::create('chambers_control_configs', function (Blueprint $table) {
       $table->id();
       $table->foreignId('chamber_id')->constrained('chambers_chambers');
       $table->string('control_type', 50);
       $table->string('mode', 50)->default('auto_schedule');
       $table->boolean('is_enabled')->default(false);
       $table->timestamps();
       
       $table->index(['chamber_id', 'control_type']);
   });
   ```

2. **修改表迁移**
   ```php
   Schema::table('chambers_control_logs', function (Blueprint $table) {
       $table->string('command_id', 64)->nullable()->after('action');
       $table->string('ack_status', 20)->nullable()->after('command_id');
       $table->timestamp('ack_at')->nullable()->after('ack_status');
   });
   ```

## MQTT 通信规范

### Topic 设计

```
chambers/{deviceCode}/data          # 设备数据上报（传感器数据）
chambers/{deviceCode}/command/manual # 手动控制命令
chambers/{deviceCode}/config/auto    # 自动控制配置
chambers/{deviceCode}/ack            # 命令/配置 ACK
chambers/{deviceCode}/status         # 设备状态上报
chambers/{deviceCode}/alarm          # 设备报警
server/status                        # 服务器在线状态
```

### 消息格式

1. **数据上报**
   ```json
   {
     "timestamp": 1713985200,
     "temperature": 23.5,
     "humidity": 65.0,
     "co2_level": 800,
     "devices": {
       "cooling": false,
       "heating": true,
       "fan": false
     }
   }
   ```

2. **手动控制命令**
   ```json
   {
     "command_id": "cmd_xxx",
     "timestamp": "2026-04-24T10:00:00Z",
     "actions": {
       "cooling": true,
       "fan": true
     }
   }
   ```

3. **自动控制配置**
   ```json
   {
     "config_id": "cfg_xxx",
     "timestamp": "2026-04-24T10:00:00Z",
     "control_type": "temperature",
     "config": {
       "mode": "auto_schedule",
       "is_enabled": true,
       "schedules": [...]
     }
   }
   ```

4. **ACK**
   ```json
   {
     "command_id": "cmd_xxx",
     "status": "success",
     "executed_at": 1713985200,
     "executed_actions": {
       "cooling": true
     }
   }
   ```

### QoS 使用

- **QoS 0**：心跳、状态上报（允许丢失）
- **QoS 1**：手动控制、配置同步（至少一次送达）
- **QoS 2**：不适用（项目未使用）

## API 规范

### RESTful API 设计

```
GET    /api/admin/chambers/bases              # 基地列表
POST   /api/admin/chambers/bases              # 创建基地
GET    /api/admin/chambers/bases/{id}         # 基地详情
PUT    /api/admin/chambers/bases/{id}         # 更新基地
DELETE /api/admin/chambers/bases/{id}         # 删除基地

GET    /api/admin/chambers/chambers           # 方舱列表
POST   /api/admin/chambers/chambers           # 创建方舱
GET    /api/admin/chambers/chambers/{id}      # 方舱详情
PUT    /api/admin/chambers/chambers/{id}      # 更新方舱

POST   /api/admin/chambers/chambers/{chamber}/manual-control  # 手动控制
PUT    /api/admin/chambers/chambers/{chamber}/auto-control    # 自动控制配置
```

### 权限命名

采用 `module.resource.action` 格式：

```
chambers.bases.view, chambers.bases.create, chambers.bases.edit, chambers.bases.delete
chambers.chambers.view, chambers.chambers.create, chambers.chambers.edit, chambers.chambers.delete
chambers.monitor.view
chambers.auto_control.view, chambers.auto_control.edit
chambers.manual_control.view
chambers.control_log.view
```

### 响应格式

```json
{
  "success": true,
  "message": "操作成功",
  "data": { ... }
}

// 错误响应
{
  "success": false,
  "message": "验证失败",
  "errors": { ... }
}
```

## Docker 部署规范

### 服务架构

```yaml
services:
  app:        # Laravel + Octane (Swoole)
    ports:
      - "8000:8000"
  web:        # Nginx 反向代理
    ports:
      - "8084:80"
  db:         # MySQL 8.0
    ports:
      - "3307:3306"
  emqx:       # MQTT Broker
    ports:
      - "1883:1883"    # MQTT
      - "8083:8083"    # WebSocket
      - "18083:18083"  # Dashboard
```

### 环境变量

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=admin123

MQTT_BROKER=emqx
MQTT_PORT=1883
MQTT_USERNAME=laravel
MQTT_PASSWORD=admin123

QUEUE_CONNECTION=database
```

## 常见开发任务模板

### 1. 新增 Filament Resource

```bash
php artisan make:filament-resource ResourceName
```

文件结构：
```
app/Admin/Resources/Chambers/
└── NewResources/
    ├── NewResource.php
    └── Pages/
        ├── CreateNew.php
        ├── EditNew.php
        └── ListNews.php
```

### 2. 新增 API 控制器

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\ModelName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // 检查权限
        if ($response = $this->checkPermission('module.resource.view')) {
            return $response;
        }
        
        // 查询逻辑
        $items = ModelName::query()
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        if ($response = $this->checkPermission('module.resource.create')) {
            return $response;
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // ...
        ]);
        
        $item = ModelName::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => '创建成功',
            'data' => $item,
        ], 201);
    }
}
```

### 3. 新增 MQTT Topic 处理

在 `MqttConsumerProcess.php` 中添加：

```php
// 订阅新 Topic
$client->subscribe('chambers/+/new_topic', function ($topic, $message) {
    $this->handleNewTopic($topic, $message);
}, $config['qos']);

// 处理方法
protected function handleNewTopic(string $topic, string $message): void
{
    try {
        $data = json_decode($message, true);
        if (!$data) {
            return;
        }
        
        // 业务逻辑
        \Log::info('New topic received', [
            'topic' => $topic,
            'data' => $data,
        ]);
        
    } catch (\Exception $e) {
        \Log::error('New topic processing error: ' . $e->getMessage());
    }
}
```

### 4. 新增数据库迁移

```bash
php artisan make:migration create_table_name_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_table_name', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamber_id')->constrained('chambers_chambers');
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
            
            $table->index(['chamber_id', 'code']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('module_table_name');
    }
};
```

### 5. 新增队列 Job

```bash
php artisan make:job SendMqttNewJob
```

```php
<?php

namespace App\Jobs;

use App\Services\MqttPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMqttNewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 30;
    
    public function __construct(
        public int $chamberId,
        public string $deviceCode,
        public array $data,
    ) {}
    
    public function handle(): void
    {
        try {
            $commandId = MqttPublisher::publishNewTopic(
                $this->deviceCode,
                $this->data
            );
            
            Log::info('MQTT new topic sent', [
                'chamber_id' => $this->chamberId,
                'command_id' => $commandId,
            ]);
            
        } catch (\Exception $e) {
            Log::error('MQTT new topic failed', [
                'error' => $e->getMessage(),
            ]);
            
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
```

## 关键命令速查

### 开发环境

**前提**：以下命令在 `laravel_app` 容器内执行（`docker compose exec app bash` 进入）

```bash
# 启动所有服务（宿主机执行）
docker compose up -d

# 进入 PHP 容器
docker compose exec app bash

# 运行开发服务器（热重载）
composer run dev

# 运行队列工作器
php artisan queue:work

# 运行测试
php artisan test

# 清除缓存
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 数据库

**PHP/Artisan 命令**在 `laravel_app` 容器内执行：
```bash
# 运行迁移
php artisan migrate

# 回滚迁移
php artisan migrate:rollback

# 创建迁移
php artisan make:migration create_table_name
```

**MySQL 访问**（容器外执行）：
```bash
# 使用 docker compose（服务名 db）
docker compose exec db mysql -uroot -padmin123 laravel

# 或使用 docker（容器名 laravel_db）
docker exec -it laravel_db mysql -uroot -padmin123 laravel
```

### Filament
```bash
# 创建资源
php artisan make:filament-resource ResourceName

# 创建页面
php artisan make:filament-page PageName

# 升级 Filament
php artisan filament:upgrade
```

### 代码质量
```bash
# 格式化代码
./vendor/bin/pint

# 检查格式（不修改）
./vendor/bin/pint --test
```

## 文件组织规范

```
laravel/
├── app/
│   ├── Admin/Resources/          # Filament 资源
│   ├── Console/Commands/         # Artisan 命令
│   ├── Http/Controllers/Api/     # API 控制器
│   ├── Jobs/                     # 队列任务
│   ├── Models/                   # Eloquent 模型
│   ├── Octane/Processes/         # Octane 进程（MQTT Consumer）
│   ├── Services/                 # 业务服务
│   └── Providers/                # 服务提供者
├── config/
│   ├── mqtt.php                  # MQTT 配置
│   └── octane.php                # Octane 配置
├── database/
│   └── migrations/               # 数据库迁移
├── resources/
│   └── views/                    # Blade 视图
├── routes/
│   ├── api.php                   # API 路由
│   └── web.php                   # Web 路由（Filament）
├── scripts/                      # 设备模拟器脚本
│   ├── device_publisher.py       # 数据上报
│   ├── device_subscriber.py      # 命令监听
│   └── simulate-device.php       # PHP 模拟器
├── docker-compose.yml            # Docker 编排
├── Dockerfile                    # 应用镜像
├── supervisord.conf              # Supervisor 配置
└── PROJECT_REQUIREMENTS.md       # 需求文档
```

## 注意事项

### Docker 容器命令对照

本项目使用 Docker Compose，服务名和容器名不同：

| 服务 | docker compose 命令 | docker 命令 |
|------|-------------------|-------------|
| PHP/Artisan | `docker compose exec app ...` | `docker exec laravel_app ...` |
| MySQL | `docker compose exec db ...` | `docker exec laravel_db ...` |
| EMQX | `docker compose exec emqx ...` | `docker exec laravel_emqx ...` |
| Nginx | `docker compose exec web ...` | `docker exec laravel_web ...` |

**示例**：
```bash
# 两种方式等价
docker compose exec app php artisan migrate
docker exec laravel_app php artisan migrate

docker compose restart app
docker restart laravel_app
```

1. **Octane 缓存**：修改 PHP 文件后需要重启 `docker compose restart app`（或 `docker restart laravel_app`）
2. **MQTT 连接**：使用 `dev_devices.code` 作为设备标识，不是 `chambers.code`
3. **队列任务**：MQTT 命令和配置同步必须通过队列异步执行
4. **数据库表名**：使用带模块前缀的表名（如 `chambers_chambers`）
5. **权限命名**：使用 `module.resource.action` 格式
6. **Filament 4.0**：表单组件使用 `Forms\Components`，不是 `Forms\Components`
7. **通知规范**：所有保存操作必须使用 Filament 原生通知
8. **温度控制**：固定为 `auto_schedule` 模式

## 故障排查

### MQTT 连接问题
- 检查 EMQX 是否运行：`docker ps | grep emqx`
- 检查设备认证：`EMQX Dashboard → 访问控制 → 认证`
- 检查客户端连接：`EMQX Dashboard → 监控 → 客户端`

### 队列问题
- 检查队列工作器：`php artisan queue:status`（如可用）
- 检查失败任务：`php artisan queue:failed`
- 重试失败任务：`php artisan queue:retry all`

### Octane 问题
- 重启 Octane：`docker compose restart app`（或 `docker restart laravel_app`）
- 检查日志：`docker compose logs app`（或 `docker logs laravel_app`）
- 清除缓存：`php artisan octane:reload`（如可用）
