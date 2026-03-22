<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 2;
    protected static ?string $heading = "最新报警";

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Alert::query()
                    ->with("chamber")
                    ->where("is_acknowledged", false)
                    ->orderBy("created_at", "desc")
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make("created_at")
                    ->label("时间")
                    ->dateTime("m-d H:i"),
                Tables\Columns\TextColumn::make("chamber.name")
                    ->label("方舱"),
                Tables\Columns\BadgeColumn::make("level")
                    ->label("级别"),
                Tables\Columns\TextColumn::make("title")
                    ->label("标题")
                    ->limit(30),
            ])
            ->paginated(false);
    }
}
