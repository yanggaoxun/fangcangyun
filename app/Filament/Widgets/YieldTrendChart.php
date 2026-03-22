<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use Filament\Widgets\ChartWidget;

class YieldTrendChart extends ChartWidget
{
    protected ?string $heading = '近7天产量趋势';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $dates = [];
        $actualYields = [];
        $expectedYields = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m-d');

            $actualYields[] = Batch::whereDate('actual_harvest_date', $date)
                ->sum('actual_yield') ?? 0;

            $expectedYields[] = Batch::whereDate('expected_harvest_date', $date)
                ->sum('expected_yield') ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => '实际产量 (kg)',
                    'data' => $actualYields,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => '预计产量 (kg)',
                    'data' => $expectedYields,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
