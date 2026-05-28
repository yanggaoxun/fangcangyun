<?php

namespace App\Admin\Widgets;

use App\Models\MushroomBatch;
use Filament\Widgets\ChartWidget;

class YieldTrendChart extends ChartWidget
{
    protected ?string $heading = '近7天产量趋势';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $dates = [];
        $actualYields = [];
        $expectedYields = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m-d');

            $actualYields[] = MushroomBatch::whereDate('actual_harvest_date', $date)
                ->sum('actual_yield') ?? 0;

            $expectedYields[] = MushroomBatch::whereDate('expected_harvest_date', $date)
                ->sum('expected_yield') ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => '实际产量 (kg)',
                    'data' => $actualYields,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => 'rgba(16, 185, 129, 1)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                ],
                [
                    'label' => '预计产量 (kg)',
                    'data' => $expectedYields,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.08)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 8,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                        'padding' => 8,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                        'padding' => 8,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
