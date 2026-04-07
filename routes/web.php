<?php

use App\Http\Controllers\Api\ChamberAutoControlController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 兼容生产环境的logout GET请求
Route::get('/admin/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/admin/login');
})->name('filament.admin.auth.logout.get');

// 自动控制 API 路由（供后台界面使用）
Route::middleware(['web'])->prefix('api/auto-control')->group(function () {
    Route::get('/{deviceCode}', [ChamberAutoControlController::class, 'getConfig'])
        ->name('web.auto-control.config');

    Route::put('/{deviceCode}/{controlType}', [ChamberAutoControlController::class, 'updateConfig'])
        ->name('web.auto-control.update');

    Route::post('/{deviceCode}/{controlType}/control', [ChamberAutoControlController::class, 'manualControl'])
        ->name('web.auto-control.manual');

    Route::get('/{deviceCode}/status', [ChamberAutoControlController::class, 'getDeviceStatus'])
        ->name('web.auto-control.status');

    Route::get('/{deviceCode}/logs', [ChamberAutoControlController::class, 'getLogs'])
        ->name('web.auto-control.logs');

    Route::get('/{deviceCode}/{controlType}/schedules', [ChamberAutoControlController::class, 'getSchedules'])
        ->name('web.auto-control.schedules');

    Route::put('/{deviceCode}/{controlType}/schedules/{scheduleIndex}', [ChamberAutoControlController::class, 'updateSchedule'])
        ->name('web.auto-control.schedule.update');
});
