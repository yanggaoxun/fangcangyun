# API 使用文档 - Sanctum 认证

## 1. 认证流程

### 1.1 登录
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password",
  "device_name": "iPhone 15"
}
```

**成功响应：**
```json
{
  "message": "登录成功",
  "token": "1|abc123def456...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "张三",
    "email": "user@example.com",
    "base_id": 1,
    "roles": ["base_admin"],
    "permissions": ["chamber.view", "environment.view"]
  }
}
```

### 1.2 使用 Token
```http
Authorization: Bearer 1|abc123def456...
```

### 1.3 登出
```http
POST /api/auth/logout
Authorization: Bearer 1|abc123def456...
```

### 1.4 获取当前用户信息
```http
GET /api/auth/user
Authorization: Bearer 1|abc123def456...
```

### 1.5 刷新 Token
```http
POST /api/auth/refresh
Authorization: Bearer 1|abc123def456...
```

### 1.6 登出所有设备
```http
POST /api/auth/logout-all
Authorization: Bearer 1|abc123def456...
```

## 2. 受保护的 API 端点

所有需要认证的 API 都需要在 Header 中添加 `Authorization: Bearer {token}`。

### 方舱监控 API
```http
GET /api/admin/chambers/monitor
Authorization: Bearer {token}

# Query Parameters:
# - base_id: 基地ID (可选)
# - chamber_id: 方舱ID (可选)
# - is_anomaly: 是否异常 true/false (可选)
# - from: 开始日期 YYYY-MM-DD (可选)
# - to: 结束日期 YYYY-MM-DD (可选)
# - per_page: 每页数量 1-100 (可选，默认15)
```

**获取最新监控数据：**
```http
GET /api/admin/chambers/monitor/latest
Authorization: Bearer {token}

# Query Parameters:
# - base_id: 基地ID (可选)
# - chamber_id: 方舱ID (可选)
```

**上传环境数据：**
```http
POST /api/admin/chambers/monitor
Authorization: Bearer {token}
Content-Type: application/json

{
  "chamber_code": "CH001",
  "temperature": 24.5,
  "humidity": 65.0,
  "co2_level": 800,
  "light_intensity": 1500
}
```

**批量上传环境数据：**
```http
POST /api/admin/chambers/monitor/batch
Authorization: Bearer {token}
Content-Type: application/json

{
  "chamber_code": "CH001",
  "data": [
    {
      "temperature": 24.5,
      "humidity": 65.0,
      "co2_level": 800,
      "recorded_at": "2026-04-12T10:00:00Z"
    }
  ]
}
```

## 3. 错误响应

### 401 - 未认证
```json
{
  "message": "Unauthenticated."
}
```

### 403 - 无权限（可以扩展中间件实现）
```json
{
  "message": "无权访问此资源"
}
```

### 422 - 验证错误
```json
{
  "message": "邮箱或密码不正确",
  "errors": {
    "email": ["邮箱或密码不正确"]
  }
}
```

## 4. RBAC 权限检查

登录后，响应中包含用户的 `permissions` 数组。App 端可以根据这些权限来控制界面元素的显示。

后端权限检查示例（可以在控制器中使用）：
```php
if (!$request->user()->hasPermission('chamber.control')) {
    return response()->json(['message' => '无权操作'], 403);
}
```

## 5. Token 有效期

- **默认有效期**：无限制（长期有效）
- **如需设置过期时间**：在 `config/sanctum.php` 中配置 `expiration`

## 6. 安全建议

1. **使用 HTTPS**：生产环境必须使用 HTTPS
2. **Token 存储**：App 端使用安全存储（iOS Keychain / Android Keystore）
3. **Token 刷新**：定期刷新 Token（如每月一次）
4. **异常处理**：Token 过期或失效时，跳转登录页

## 7. 多设备登录

- 同一用户可以在多个设备登录
- 每个设备使用不同的 `device_name` 区分
- 用户可以在后台查看所有登录设备
- `logout` 只退出当前设备，`logout-all` 退出所有设备
