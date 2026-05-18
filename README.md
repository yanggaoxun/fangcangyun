# 菌菇方舱自动化管理系统

基于 Laravel 12 + Filament 4.0 开发的菌菇类方舱自动化后台管理系统，用于管理和监控蘑菇 cultivation chambers 的自动化生产过程。

## 技术栈

- **后端框架**: Laravel 12 (PHP 8.2+)
- **管理面板**: Filament 4.0
- **数据库**: MySQL 8.0
- **消息队列**: Redis / Database
- **容器化**: Docker + Docker Compose
- **MQTT Broker**: EMQX 5.8.6（设备通信）

## 快速开始

### 环境要求
- Docker & Docker Compose
- PHP 8.2+ (本地开发)
- Node.js 18+ (前端构建)

### 启动项目

```bash
# 1. 启动所有服务
docker-compose up -d

# 2. 运行迁移和种子
php artisan migrate
php artisan db:seed

# 3. 启动开发服务器（热重载 + 队列 + 日志监控）
composer run dev
```

访问后台: http://localhost:8084/admin

### 常用命令

```bash
# 代码格式化
./vendor/bin/pint

# 运行测试
php artisan test

# 进入 MySQL
docker exec -it laravel_db mysql -uroot -padmin123 laravel
```

## 核心功能

### 1. 基地管理
- 基地 CRUD、状态管理
- 基地与菌种库存关联

### 2. 方舱管理
- 方舱 CRUD、状态流转（空闲→种植中→维护）
- 环境数据监控与设备控制

### 3. 菌种菌包管理
- 菌种信息管理（平菇、香菇、金针菇等）
- 基地菌种库存管理
- 种植批次管理（自动扣减库存）

### 4. 环境监控与设备控制
- **实时监控**: 温度、湿度、CO2、光照
- **手动控制**: 9个设备独立开关（制冷、加热、风机、新风、加湿、照明等）
- **自动控制**: 温度/加湿/新风/排风/光照 5种控制类型
  - 控制模式：阈值控制、定时控制、循环控制
  - 多时段配置、联动控制、延时启动
- **MQTT 通信**: 边缘设备实时上报数据，后台下发控制命令

### 5. 告警管理
- 多级告警机制（紧急/重要/一般）
- 环境异常、设备故障监控

### 6. 权限管理 (RBAC)
- 基于角色的访问控制
- 预定义角色：超级管理员、系统管理员、基地管理员
- 细粒度权限控制

## 系统架构

### Docker 服务
- **App**: PHP 8.2 + Laravel (端口 9000)
- **Web**: Nginx 反向代理 (端口 8084)
- **DB**: MySQL 8.0 (端口 3307)
- **MQTT**: EMQX Broker (端口 1883/8083/18083)

### 数据流
```
边缘设备(树莓派) → MQTT → Laravel 服务器 → MySQL
                          ↓
                    Filament 后台管理
```

## 本地化设置

项目已配置中文环境：

```env
APP_LOCALE=zh_CN
APP_FALLBACK_LOCALE=zh_CN
APP_FAKER_LOCALE=zh_CN
```

- 已创建 `lang/zh_CN/` 语言文件（认证、分页、密码、验证）
- Filament 内置组件已加载中文翻译
- 所有管理界面显示为中文

## 项目文档

| 文档 | 说明 |
|------|------|
| [PROJECT.md](PROJECT.md) | 项目详细需求文档、数据库设计、更新历史 |
| [API_GUIDE.md](API_GUIDE.md) | API 接口文档（认证、环境数据、设备控制、自动控制） |
| [CLAUDE.md](CLAUDE.md) | AI 开发助手指南（命令参考、架构说明） |
| [scripts/README_AUTO_CONTROL.md](scripts/README_AUTO_CONTROL.md) | 边缘设备自动控制程序说明 |

## 目录结构

```
laravel/
├── app/
│   ├── Admin/Resources/    # Filament 资源
│   ├── Http/Controllers/   # API 控制器
│   ├── Models/             # Eloquent 模型
│   └── Services/           # 业务服务
├── database/migrations/    # 数据库迁移
├── scripts/                # 边缘设备程序（Python）
│   ├── device_worker.py    # 主守护进程
│   ├── auto_control.py     # 自动控制
│   ├── mqtt_handler.py     # MQTT 客户端
│   └── device_controller.py # GPIO 控制
├── routes/                 # 路由定义
└── resources/views/        # 视图文件
```

## 许可证

MIT
