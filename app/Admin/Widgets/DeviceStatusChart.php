<?php

namespace App\Admin\Widgets;

use App\Models\DevDevice;
use Filament\Widgets\ChartWidget;

class DeviceStatusChart extends ChartWidget
{
    protected ?string $heading = '设备状态分布';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $activeCount = DevDevice::where('status', 'active')->count();
        $inactiveCount = DevDevice::where('status', 'inactive')->count();
        $maintenanceCount = DevDevice::where('status', 'maintenance')->count();
        $errorCount = DevDevice::where('status', 'error')->count();

        return [
            'datasets' => [
                [
                    'label' => '设备数量',
                    'data' => [$activeCount, $inactiveCount, $maintenanceCount, $errorCount],
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',   // 绿色 - 运行中
                        'rgba(148, 163, 184, 0.8)',  // 灰色 - 停用
                        'rgba(245, 158, 11, 0.8)',   // 琥珀色 - 维护中
                        'rgba(239, 68, 68, 0.8)',    // 红色 - 故障
                    ],
                    'borderColor' => [
                        'rgba(16, 185, 129, 1)',
                        'rgba(148, 163, 184, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    'borderWidth' => 0,
                    'borderRadius' => 4,
                    'barThickness' => 32,
                ],
            ],
            'labels' => ['运行中', '停用', '维护中', '故障'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
