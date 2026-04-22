<?php

namespace App\Admin\Resources\Chambers\Chambers\Pages;

use App\Admin\Resources\Chambers\Chambers\ChamberResource;
use App\Models\Chamber;
use App\Models\ChamberBase;
use App\Models\ChamberManualControl;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use UnitEnum;

class ChamberMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ChamberResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = '方舱监控';

    protected static string|UnitEnum|null $navigationGroup = '方舱管理';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.resources.chambers.pages.chamber-monitoring';

    #[Url]
    public ?int $baseFilter = null;

    public function getTitle(): string
    {
        return '方舱监控';
    }

    public function getBreadcrumbs(): array
    {
        return [
            ChamberResource::getUrl() => '方舱管理',
            '#' => '方舱监控',
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    public function toggleDevice(int $chamberId, string $device): void
    {
        $record = ChamberManualControl::where('chamber_id', $chamberId)->latest('recorded_at')->first();

        if (! $record) {
            Notification::make()
                ->title('错误')
                ->body('未找到设备状态数据')
                ->danger()
                ->send();

            return;
        }

        $currentState = $record->$device ?? false;
        $newState = ! $currentState;

        $record->update([
            $device => $newState,
            'recorded_at' => now(),
        ]);

        $deviceNames = ChamberManualControl::getDeviceNames();
        $deviceName = $deviceNames[$device] ?? $device;

        Notification::make()
            ->title('操作成功')
            ->body("{$deviceName} 已切换为 ".($newState ? '开启' : '关闭'))
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ChamberManualControl::query()->with(['chamber.base', 'chamber']))
            ->columns([
                TextColumn::make('chamber.base.name')
                    ->label('基地')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('chamber.name')
                    ->label('方舱')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('temperature')
                    ->label('温度')
                    ->suffix(' °C')
                    ->sortable(),

                TextColumn::make('humidity')
                    ->label('湿度')
                    ->suffix(' %')
                    ->sortable(),

                TextColumn::make('co2_level')
                    ->label('CO2浓度')
                    ->suffix(' ppm')
                    ->sortable(),

                IconColumn::make('is_anomaly')
                    ->label('异常')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('recorded_at')
                    ->label('记录时间')
                    ->dateTime('Y年 n月j日 H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->actions([
                Action::make('device_control')
                    ->label('手动控制')
                    ->icon('heroicon-o-cog')
                    ->color('primary')
                    ->modalHeading('手动控制')
                    ->modalWidth('5xl')
                    ->modalContent(fn ($record) => view('filament.resources.chambers.pages.device-control-modal', [
                        'chamberId' => $record->chamber_id,
                    ])),
                Action::make('auto_control')
                    ->label('自动控制')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('success')
                    ->modalHeading('自动控制配置')
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn ($record) => view('filament.resources.chambers.pages.auto-control-modal', [
                        'chamberId' => $record->chamber_id,
                    ])),
            ])
            ->filters([
                Filter::make('base_id')
                    ->label('基地')
                    ->form([
                        Select::make('value')
                            ->label('基地')
                            ->options(fn (): array => ChamberBase::where('status', 'active')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->default(fn () => $this->baseFilter)
                            ->afterStateUpdated(fn ($state) => $this->baseFilter = $state),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], fn (Builder $query, $baseId): Builder => $query->whereHas('chamber', fn ($q): Builder => $q->where('base_id', $baseId)));
                    }),

                Filter::make('chamber_id')
                    ->label('方舱')
                    ->form([
                        Select::make('value')
                            ->label('方舱')
                            ->options(function (): array {
                                $baseId = $this->baseFilter;
                                if (! $baseId) {
                                    return [];
                                }

                                return Chamber::where('base_id', $baseId)->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], fn (Builder $query, $chamberId): Builder => $query->where('chamber_id', $chamberId));
                    }),

                Filter::make('recorded_at')
                    ->label('记录时间')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('开始日期'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('recorded_at', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date): Builder => $query->whereDate('recorded_at', '<=', $date));
                    }),

                SelectFilter::make('is_anomaly')
                    ->label('异常状态')
                    ->options([
                        '1' => '异常',
                        '0' => '正常',
                    ]),
            ]);
    }
}
