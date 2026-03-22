<?php

namespace App\Filament\Widgets;

use App\Models\Chamber;
use Filament\Widgets\ChartWidget;

class ChamberStatusChart extends ChartWidget
{
    protected ?string $heading = '方舱状态分布';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $idleCount = Chamber::where('status', 'idle')->count();
        $plantingCount = Chamber::where('status', 'planting')->count();
        $maintenanceCount = Chamber::where('status', 'maintenance')->count();

        return [
            'datasets' => [
                [
                    'label' => '方舱数量',
                    'data' => [$idleCount, $plantingCount, $maintenanceCount],
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.6)',   // 蓝色 - 空闲
                        'rgba(75, 192, 192, 0.6)',   // 绿色 - 种植中
                        'rgba(255, 206, 86, 0.6)',   // 黄色 - 维护中
                    ],
                    'borderColor' => [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['空闲', '种植中', '维护中'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
