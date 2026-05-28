# Dashboard Redesign Implementation Plan

**Goal:** Redesign the admin dashboard with a modern, information-rich layout (Plan C) that balances production operations overview with real-time monitoring status.

**Architecture:** Reorganize existing Filament widgets into a layered layout: top stats cards with sparklines, middle charts (line + doughnut, bar + pie), bottom tables and quick actions. Add CSS styling for a cohesive look.

**Tech Stack:** Laravel 12, Filament 4.0, Chart.js (via Filament Charts), Tailwind CSS, custom CSS

---

## File Structure

| File | Action | Description |
|------|--------|-------------|
| `app/Admin/Widgets/DashboardStatsWidget.php` | Modify | Redesign to 4 stat cards with icons and sparklines |
| `app/Admin/Widgets/YieldTrendChart.php` | Modify | Enhanced area chart with better styling |
| `app/Admin/Widgets/ChamberStatusChart.php` | Modify | Clean doughnut chart |
| `app/Admin/Widgets/DeviceStatusChart.php` | Modify | Convert to horizontal bar chart |
| `app/Admin/Widgets/AlertLevelChart.php` | Create | Pie chart showing alert level distribution |
| `app/Admin/Widgets/QuickActionsWidget.php` | Create | Quick action buttons grid |
| `app/Admin/Widgets/BatchProgressWidget.php` | Modify | Improved table styling |
| `app/Admin/Widgets/RecentAlertsWidget.php` | Modify | Improved table styling |
| `app/Admin/Pages/Dashboard.php` | Modify | Ensure proper widget registration |
| `resources/css/filament/admin/custom.css` | Modify | Add dashboard-specific styles |

---

## Task 1: Redesign DashboardStatsWidget

**Files:**
- Modify: `app/Admin/Widgets/DashboardStatsWidget.php`

Change to 4 key stats with color-coded icons and sparkline charts:
1. 方舱利用率 (utilization rate)
2. 活跃批次 (active batches) 
3. 设备在线率 (device online rate)
4. 未处理报警 (unacknowledged alerts)

Remove the other 2 stats (bases and yield) to reduce clutter.

## Task 2: Enhance YieldTrendChart

**Files:**
- Modify: `app/Admin/Widgets/YieldTrendChart.php`

Update to use a cleaner area chart style with proper colors.

## Task 3: Clean ChamberStatusChart

**Files:**
- Modify: `app/Admin/Widgets/ChamberStatusChart.php`

Simplify the doughnut chart with cleaner colors and legend.

## Task 4: Convert DeviceStatusChart to Bar Chart

**Files:**
- Modify: `app/Admin/Widgets/DeviceStatusChart.php`

Convert from pie to horizontal bar chart for better readability.

## Task 5: Create AlertLevelChart

**Files:**
- Create: `app/Admin/Widgets/AlertLevelChart.php`

Create a pie chart showing alert distribution by level (critical, warning, info).

## Task 6: Create QuickActionsWidget

**Files:**
- Create: `app/Admin/Widgets/QuickActionsWidget.php`

Create a widget with quick action buttons for common tasks.

## Task 7: Improve BatchProgressWidget

**Files:**
- Modify: `app/Admin/Widgets/BatchProgressWidget.php`

Improve table columns and add visual indicators.

## Task 8: Improve RecentAlertsWidget

**Files:**
- Modify: `app/Admin/Widgets/RecentAlertsWidget.php`

Improve table columns with better badge styling.

## Task 9: Update Dashboard Page

**Files:**
- Modify: `app/Admin/Pages/Dashboard.php`

Verify widgets are properly discovered.

## Task 10: Add Dashboard CSS Styles

**Files:**
- Modify: `resources/css/filament/admin/custom.css`

Add dashboard card styling, chart containers, and responsive adjustments.

## Verification

After implementation:
1. Visit `/admin` to verify layout
2. Check all widgets render correctly
3. Verify responsive behavior on different screen sizes
4. Ensure charts display data properly
