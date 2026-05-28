<?php

namespace App\Admin\Widgets;

use App\Models\Chamber;
use App\Models\DevDevice;
use App\Models\MushroomBatch;
use App\Models\SysAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        // 方舱统计
        $chamberCount = Chamber::count();
        $chamberPlantingCount = Chamber::where('status', 'planting')->count();
        $chamberUtilizationRate = $chamberCount > 0
            ? round(($chamberPlantingCount / $chamberCount) * 100, 1)
            : 0;

        // 批次统计
        $activeBatches = MushroomBatch::whereNull('actual_harvest_date')->count();
        $todayNewBatches = MushroomBatch::whereDate('created_at', today())->count();

        // 设备统计
        $deviceCount = DevDevice::count();
        $deviceActiveCount = DevDevice::where('status', 'active')->count();
        $deviceOnlineRate = $deviceCount > 0
            ? round(($deviceActiveCount / $deviceCount) * 100, 1)
            : 0;

        // 报警统计
        $unacknowledgedAlerts = SysAlert::where('is_acknowledged', false)->count();
        $criticalAlerts = SysAlert::where('is_acknowledged', false)
            ->where('level', 'critical')
            ->count();

        return [
            Stat::make('方舱利用率', $chamberUtilizationRate.'%')
                ->description($chamberPlantingCount.' / '.$chamberCount.' 方舱种植中')
                ->descriptionIcon('heroicon-m-square-3-stack-3d')
                ->color($chamberUtilizationRate >= 70 ? 'success' : ($chamberUtilizationRate >= 40 ? 'warning' : 'danger'))
                ->chart([
                    $chamberPlantingCount,
                    max(0, $chamberCount - $chamberPlantingCount),
                ])
                ->extraAttributes([
                    'class' => 'dashboard-stat-card dashboard-stat-green',
                ]),

            Stat::make('活跃批次', $activeBatches)
                ->description($todayNewBatches > 0 ? '今日新增 '.$todayNewBatches.' 个' : '暂无新增')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->chart([
                    $activeBatches,
                    max(1, $activeBatches + 3),
                ])
                ->extraAttributes([
                    'class' => 'dashboard-stat-card dashboard-stat-blue',
                ]),

            Stat::make('设备在线率', $deviceOnlineRate.'%')
                ->description($deviceActiveCount.' / '.$deviceCount.' 设备在线')
                ->descriptionIcon('heroicon-m-wifi')
                ->color($deviceOnlineRate >= 90 ? 'success' : ($deviceOnlineRate >= 70 ? 'warning' : 'danger'))
                ->chart([
                    $deviceActiveCount,
                    max(0, $deviceCount - $deviceActiveCount),
                ])
                ->extraAttributes([
                    'class' => 'dashboard-stat-card dashboard-stat-indigo',
                ]),

            Stat::make('未处理报警', $unacknowledgedAlerts)
                ->description($criticalAlerts > 0 ? $criticalAlerts.' 个严重报警' : ($unacknowledgedAlerts > 0 ? '待处理中' : '系统正常'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($criticalAlerts > 0 ? 'danger' : ($unacknowledgedAlerts > 0 ? 'warning' : 'success'))
                ->chart([
                    $criticalAlerts,
                    max(0, $unacknowledgedAlerts - $criticalAlerts),
                ])
                ->extraAttributes([
                    'class' => 'dashboard-stat-card dashboard-stat-amber',
                ]),
        ];
    }
}
