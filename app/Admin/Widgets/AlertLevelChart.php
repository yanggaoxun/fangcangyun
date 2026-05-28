<?php

namespace App\Admin\Widgets;

use App\Models\SysAlert;
use Filament\Widgets\ChartWidget;

class AlertLevelChart extends ChartWidget
{
    protected ?string $heading = '报警级别分布';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $criticalCount = SysAlert::where('is_acknowledged', false)
            ->where('level', 'critical')
            ->count();

        $warningCount = SysAlert::where('is_acknowledged', false)
            ->where('level', 'warning')
            ->count();

        $infoCount = SysAlert::where('is_acknowledged', false)
            ->where('level', 'info')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => '报警数量',
                    'data' => [$criticalCount, $warningCount, $infoCount],
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',    // 红色 - 严重
                        'rgba(245, 158, 11, 0.8)',   // 琥珀色 - 警告
                        'rgba(59, 130, 246, 0.8)',   // 蓝色 - 信息
                    ],
                    'borderColor' => [
                        'rgba(239, 68, 68, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(59, 130, 246, 1)',
                    ],
                    'borderWidth' => 0,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['严重', '警告', '信息'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
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
        ];
    }
}
