<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChamberBaseController;
use App\Http\Controllers\Api\ChamberChamberController;
use App\Http\Controllers\Api\ChamberControlApiController;
use App\Http\Controllers\Api\ChamberMonitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication routes (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.auth.login');
});

// Protected routes (auth required)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');

        Route::post('/logout-all', [AuthController::class, 'logoutAll'])
            ->name('api.auth.logout-all');

        Route::get('/user', [AuthController::class, 'user'])
            ->name('api.auth.user');

        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('api.auth.refresh');
    });

    // Chamber Monitor routes (matches admin URL structure)
    Route::prefix('admin/chambers/monitor')->group(function () {
        Route::get('/', [ChamberMonitorController::class, 'index'])
            ->name('api.admin.chambers.monitor.index');

        Route::get('/latest', [ChamberMonitorController::class, 'latest'])
            ->name('api.admin.chambers.monitor.latest');

        Route::post('/', [ChamberMonitorController::class, 'store'])
            ->name('api.admin.chambers.monitor.store');

        Route::post('/batch', [ChamberMonitorController::class, 'batchStore'])
            ->name('api.admin.chambers.monitor.batch');
    });

    // Chamber Base routes (matches admin URL structure)
    Route::prefix('admin/chambers/bases')->group(function () {
        Route::get('/', [ChamberBaseController::class, 'index'])
            ->name('api.admin.chambers.bases.index');

        Route::get('/{base}', [ChamberBaseController::class, 'show'])
            ->name('api.admin.chambers.bases.show');

        Route::post('/', [ChamberBaseController::class, 'store'])
            ->name('api.admin.chambers.bases.store');

        Route::put('/{base}', [ChamberBaseController::class, 'update'])
            ->name('api.admin.chambers.bases.update');

        Route::delete('/{base}', [ChamberBaseController::class, 'destroy'])
            ->name('api.admin.chambers.bases.destroy');
    });

    // Chamber Chamber routes (matches admin URL structure)
    Route::prefix('admin/chambers/chambers')->group(function () {
        Route::get('/', [ChamberChamberController::class, 'index'])
            ->name('api.admin.chambers.chambers.index');

        Route::get('/{chamber}', [ChamberChamberController::class, 'show'])
            ->name('api.admin.chambers.chambers.show');

        Route::post('/', [ChamberChamberController::class, 'store'])
            ->name('api.admin.chambers.chambers.store');

        Route::put('/{chamber}', [ChamberChamberController::class, 'update'])
            ->name('api.admin.chambers.chambers.update');

        Route::delete('/{chamber}', [ChamberChamberController::class, 'destroy'])
            ->name('api.admin.chambers.chambers.destroy');

        // 自动控制配置
        Route::get('/{chamber}/auto-control', [ChamberControlApiController::class, 'show'])
            ->name('api.admin.chambers.chambers.auto-control.show');

        Route::get('/{chamber}/auto-control/status', [ChamberControlApiController::class, 'status'])
            ->name('api.admin.chambers.chambers.auto-control.status');

        Route::get('/{chamber}/auto-control/logs', [ChamberControlApiController::class, 'logs'])
            ->name('api.admin.chambers.chambers.auto-control.logs');

        Route::get('/{chamber}/auto-control/schedules', [ChamberControlApiController::class, 'schedules'])
            ->name('api.admin.chambers.chambers.auto-control.schedules');

        Route::put('/{chamber}/auto-control/schedules', [ChamberControlApiController::class, 'updateSchedule'])
            ->name('api.admin.chambers.chambers.auto-control.schedule.update');

        Route::put('/{chamber}/auto-control/{controlType}', [ChamberControlApiController::class, 'update'])
            ->name('api.admin.chambers.chambers.auto-control.update');

        // 手动控制
        Route::post('/{chamber}/manual-control', [ChamberControlApiController::class, 'manualControl'])
            ->name('api.admin.chambers.chambers.manual-control');
    });

});
