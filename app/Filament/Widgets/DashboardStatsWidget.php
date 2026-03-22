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

    protected function getStats(): array
    {
        // 基地统计
        $baseCount = Base::count();
        $baseActiveCount = Base::where('status', 'active')->count();

        // 方舱统计
        $chamberCount = Chamber::count();
        $chamberPlantingCount = Chamber::where('status', 'planting')->count();
        $chamberUtilizationRate = $chamberCount > 0
            ? round(($chamberPlantingCount / $chamberCount) * 100, 1)
            : 0;

        // 批次统计
        $activeBatches = Batch::whereNull('actual_harvest_date')->count();
        $todayNewBatches = Batch::whereDate('created_at', today())->count();

        // 设备统计
        $deviceCount = Device::count();
        $deviceActiveCount = Device::where('status', 'active')->count();
        $deviceOnlineRate = $deviceCount > 0
            ? round(($deviceActiveCount / $deviceCount) * 100, 1)
            : 0;

        // 报警统计
        $unacknowledgedAlerts = Alert::where('is_acknowledged', false)->count();
        $criticalAlerts = Alert::where('is_acknowledged', false)
            ->where('level', 'critical')
            ->count();

        // 今日产量
        $todayYield = Batch::whereDate('actual_harvest_date', today())
            ->sum('actual_yield') ?? 0;

        return [
            Stat::make('运营基地', $baseCount)
                ->description("{$baseActiveCount} 个正常运营")
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('总方舱数', $chamberCount)
                ->description("{$chamberPlantingCount} 个种植中 ({$chamberUtilizationRate}%)")
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('活跃批次', $activeBatches)
                ->description("今日新增 {$todayNewBatches} 个")
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('info'),

            Stat::make('设备在线', "{$deviceActiveCount}/{$deviceCount}")
                ->description("在线率 {$deviceOnlineRate}%")
                ->descriptionIcon('heroicon-m-signal')
                ->color($deviceOnlineRate >= 90 ? 'success' : 'warning'),

            Stat::make('未处理报警', $unacknowledgedAlerts)
                ->description($criticalAlerts > 0 ? "{$criticalAlerts} 个严重报警" : '无严重报警')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($criticalAlerts > 0 ? 'danger' : 'success'),

            Stat::make('今日产量', number_format($todayYield, 2).' kg')
                ->description('实际收获重量')
                ->descriptionIcon('heroicon-m-scale')
                ->color('success'),
        ];
    }
}
