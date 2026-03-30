<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\Base;
use App\Models\Batch;
use App\Models\Chamber;
use App\Models\Device;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        // 基地统计
        $baseCount = Base::count();
        $baseActiveCount = Base::where('status', 'active')->count();
        $baseInactiveCount = $baseCount - $baseActiveCount;

        // 方舱统计
        $chamberCount = Chamber::count();
        $chamberPlantingCount = Chamber::where('status', 'planting')->count();
        $chamberIdleCount = Chamber::where('status', 'idle')->count();
        $chamberMaintenanceCount = Chamber::where('status', 'maintenance')->count();
        $chamberUtilizationRate = $chamberCount > 0
            ? round(($chamberPlantingCount / $chamberCount) * 100, 1)
            : 0;

        // 批次统计
        $activeBatches = Batch::whereNull('actual_harvest_date')->count();
        $todayNewBatches = Batch::whereDate('created_at', today())->count();
        $yesterdayNewBatches = Batch::whereDate('created_at', today()->subDay())->count();
        $batchTrend = $todayNewBatches - $yesterdayNewBatches;

        // 设备统计
        $deviceCount = Device::count();
        $deviceActiveCount = Device::where('status', 'active')->count();
        $deviceErrorCount = Device::where('status', 'error')->count();
        $deviceOnlineRate = $deviceCount > 0
            ? round(($deviceActiveCount / $deviceCount) * 100, 1)
            : 0;

        // 报警统计
        $unacknowledgedAlerts = Alert::where('is_acknowledged', false)->count();
        $criticalAlerts = Alert::where('is_acknowledged', false)
            ->where('level', 'critical')
            ->count();
        $warningAlerts = Alert::where('is_acknowledged', false)
            ->where('level', 'warning')
            ->count();

        // 产量统计
        $todayYield = Batch::whereDate('actual_harvest_date', today())
            ->sum('actual_yield') ?? 0;
        $yesterdayYield = Batch::whereDate('actual_harvest_date', today()->subDay())
            ->sum('actual_yield') ?? 0;
        $yieldTrend = $todayYield - $yesterdayYield;
        $yieldTrendPercent = $yesterdayYield > 0
            ? round((($todayYield - $yesterdayYield) / $yesterdayYield) * 100, 1)
            : 0;

        return [
            Stat::make('运营基地', $baseCount)
                ->description($baseInactiveCount > 0
                    ? "✓ {$baseActiveCount} 正常 | ✗ {$baseInactiveCount} 停用"
                    : "✓ {$baseActiveCount} 个正常运营")
                ->descriptionIcon('heroicon-m-home')
                ->color($baseInactiveCount > 0 ? 'warning' : 'success')
                ->chart([$baseActiveCount, $baseInactiveCount])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important; border-left: 4px solid #22c55e !important;',
                ]),

            Stat::make('方舱利用率', $chamberUtilizationRate.'%')
                ->description("🌱 {$chamberPlantingCount} 种植 | ⏸️ {$chamberIdleCount} 空闲 | 🔧 {$chamberMaintenanceCount} 维护")
                ->descriptionIcon('heroicon-m-square-3-stack-3d')
                ->color($chamberUtilizationRate >= 70 ? 'success' : ($chamberUtilizationRate >= 40 ? 'warning' : 'info'))
                ->chart([$chamberPlantingCount, $chamberIdleCount, $chamberMaintenanceCount])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important; border-left: 4px solid #3b82f6 !important;',
                ]),

            Stat::make('活跃批次', $activeBatches)
                ->description($todayNewBatches > 0
                    ? "📈 今日新增 {$todayNewBatches} 个"
                    : ($batchTrend > 0 ? "📊 较昨日 +{$batchTrend}" : '📋 今日无新增'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->chart([$todayNewBatches, max(0, $yesterdayNewBatches)])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%) !important; border-left: 4px solid #06b6d4 !important;',
                ]),

            Stat::make('设备在线率', $deviceOnlineRate.'%')
                ->description("✓ {$deviceActiveCount} 在线".($deviceErrorCount > 0 ? " | ✗ {$deviceErrorCount} 故障" : ' | 运行正常'))
                ->descriptionIcon('heroicon-m-wifi')
                ->color($deviceOnlineRate >= 90 ? 'success' : ($deviceOnlineRate >= 70 ? 'warning' : 'danger'))
                ->chart([$deviceActiveCount, $deviceCount - $deviceActiveCount])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%) !important; border-left: 4px solid #6366f1 !important;',
                ]),

            Stat::make('未处理报警', $unacknowledgedAlerts)
                ->description($criticalAlerts > 0
                    ? "🔴 {$criticalAlerts} 严重 | 🟡 {$warningAlerts} 警告"
                    : ($unacknowledgedAlerts > 0 ? "🟡 {$unacknowledgedAlerts} 个待处理" : '✅ 系统运行正常'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($criticalAlerts > 0 ? 'danger' : ($unacknowledgedAlerts > 0 ? 'warning' : 'success'))
                ->chart([$criticalAlerts, $warningAlerts])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important; border-left: 4px solid #f59e0b !important;',
                ]),

            Stat::make('今日产量', number_format($todayYield, 2).' kg')
                ->description($yieldTrend > 0
                    ? '📈 较昨日 +'.number_format($yieldTrend, 2).' kg (+'.($yieldTrendPercent > 0 ? $yieldTrendPercent : 0).'%)'
                    : ($yieldTrend < 0 ? '📉 较昨日 '.number_format($yieldTrend, 2).' kg ('.($yieldTrendPercent < 0 ? $yieldTrendPercent : 0).'%)' : '➡️ 与昨日持平'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($yieldTrend >= 0 ? 'success' : 'warning')
                ->chart([$todayYield, $yesterdayYield])
                ->extraAttributes([
                    'style' => 'background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%) !important; border-left: 4px solid #f43f5e !important;',
                ]),
        ];
    }
}
