# 仪表板顶部导航设计方案

## 需求
将左侧边栏中的"仪表板"导航项移动到顶部栏左侧（Logo 旁边），以文字+图标按钮形式展示。

## 设计方案

### 位置
- 顶部栏左侧，紧挨 Logo/汉堡菜单之后
- 使用 Filament `panels::topbar.start` renderHook 注入

### 视觉样式
- 默认状态：灰色文字（`#94a3b8`）+ 图表图标，透明背景
- Hover 状态：绿色文字（`#4ade80`）+ 淡绿色背景（`rgba(74, 222, 128, 0.15)`）
- Active 状态（当前在仪表板页面）：绿色文字 + 绿色左边框指示器
- 圆角 8px，padding 0.5rem 1rem
- 文字："仪表板"
- 图标：`heroicon-o-presentation-chart-line`

### 交互
- 点击后跳转到 `/admin`（仪表板页面）
- 当前页面为仪表板时显示 active 高亮状态

### 左侧边栏
- 从侧边栏中完全移除"仪表板"菜单项
- 其他菜单保持不变

## 实现文件
1. `app/Admin/Pages/Dashboard.php` — 隐藏侧边栏导航
2. `app/Providers/Filament/AdminPanelProvider.php` — 注册 renderHook
3. `resources/views/filament/components/dashboard-top-nav.blade.php` — 导航按钮视图
4. `resources/css/filament/admin/custom.css` — 按钮样式

## 效果预览
```
[Logo] [≡] [📊 仪表板]        [搜索] [🔔] [👤 用户]
```
（深色顶部栏背景，按钮 hover 时发绿光）
