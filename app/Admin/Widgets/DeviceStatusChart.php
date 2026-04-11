<?php

namespace App\Admin\Widgets;

use App\Models\Device;
use Filament\Widgets\ChartWidget;

class DeviceStatusChart extends ChartWidget
{
    protected ?string $heading = '设备状态分布';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $activeCount = Device::where('status', 'active')->count();
        $inactiveCount = Device::where('status', 'inactive')->count();
        $maintenanceCount = Device::where('status', 'maintenance')->count();
        $errorCount = Device::where('status', 'error')->count();

        return [
            'datasets' => [
                [
                    'label' => '设备数量',
                    'data' => [$activeCount, $inactiveCount, $maintenanceCount, $errorCount],
                    'backgroundColor' => [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(201, 203, 207, 0.6)',
                        'rgba(255, 205, 86, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                    ],
                ],
            ],
            'labels' => ['运行中', '停用', '维护中', '故障'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
