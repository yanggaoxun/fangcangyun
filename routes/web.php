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
    Route::get('/{chamberId}', [ChamberAutoControlController::class, 'getConfig'])
        ->name('web.auto-control.config');

    Route::put('/{chamberId}/{controlType}', [ChamberAutoControlController::class, 'updateConfig'])
        ->name('web.auto-control.update');

    Route::post('/{chamberId}/{controlType}/control', [ChamberAutoControlController::class, 'manualControl'])
        ->name('web.auto-control.manual');

    Route::get('/{chamberId}/status', [ChamberAutoControlController::class, 'getDeviceStatus'])
        ->name('web.auto-control.status');

    Route::get('/{chamberId}/logs', [ChamberAutoControlController::class, 'getLogs'])
        ->name('web.auto-control.logs');

    Route::get('/{chamberId}/{controlType}/schedules', [ChamberAutoControlController::class, 'getSchedules'])
        ->name('web.auto-control.schedules');

    Route::put('/{chamberId}/{controlType}/schedules/{scheduleIndex}', [ChamberAutoControlController::class, 'updateSchedule'])
        ->name('web.auto-control.schedule.update');
});
