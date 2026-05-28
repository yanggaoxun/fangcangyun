<?php

namespace App\Admin\Widgets;

use App\Admin\Resources\Chambers\Chambers\ChamberResource;
use App\Admin\Resources\Devices\Devices\DeviceResource;
use App\Admin\Resources\Mushroom\Batches\BatchResource;
use App\Admin\Resources\System\Alerts\AlertResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    public function getActions(): array
    {
        return [
            [
                'label' => '新建批次',
                'icon' => 'heroicon-m-plus-circle',
                'url' => BatchResource::getUrl('create'),
                'color' => 'primary',
            ],
            [
                'label' => '方舱监控',
                'icon' => 'heroicon-m-video-camera',
                'url' => ChamberResource::getUrl('index'),
                'color' => 'success',
            ],
            [
                'label' => '设备管理',
                'icon' => 'heroicon-m-cog-6-tooth',
                'url' => DeviceResource::getUrl('index'),
                'color' => 'info',
            ],
            [
                'label' => '报警中心',
                'icon' => 'heroicon-m-bell-alert',
                'url' => AlertResource::getUrl('index'),
                'color' => 'warning',
            ],
        ];
    }
}
