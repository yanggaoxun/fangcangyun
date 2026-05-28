<?php

namespace App\Admin\Widgets;

use App\Models\Chamber;
use Filament\Widgets\ChartWidget;

class ChamberStatusChart extends ChartWidget
{
    protected ?string $heading = '方舱状态分布';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

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
                        'rgba(59, 130, 246, 0.8)',   // 蓝色 - 空闲
                        'rgba(16, 185, 129, 0.8)',   // 绿色 - 种植中
                        'rgba(245, 158, 11, 0.8)',   // 琥珀色 - 维护中
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                    ],
                    'borderWidth' => 0,
                    'hoverOffset' => 4,
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
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 10,
                        'padding' => 12,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => true,
            'aspectRatio' => 1,
            'cutout' => '60%',
        ];
    }
}
