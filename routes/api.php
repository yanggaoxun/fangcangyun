<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChamberAutoControlController;
use App\Http\Controllers\Api\ChamberDeviceController;
use App\Http\Controllers\Api\EnvironmentDataController;
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

    // Environment data endpoints for chambers
    Route::prefix('v1')->group(function () {
        Route::prefix('environment-data')->group(function () {
            Route::post('/', [EnvironmentDataController::class, 'store'])
                ->name('api.environment-data.store');

            Route::post('/batch', [EnvironmentDataController::class, 'batchStore'])
                ->name('api.environment-data.batch');
        });

        // Chamber device control endpoints
        Route::prefix('chambers')->group(function () {
            Route::get('/{deviceCode}/devices', [ChamberDeviceController::class, 'getStatus'])
                ->name('api.chamber.devices.status');

            Route::post('/{deviceCode}/devices', [ChamberDeviceController::class, 'updateStatus'])
                ->name('api.chamber.devices.update');

            Route::post('/{deviceCode}/devices/control', [ChamberDeviceController::class, 'controlDevice'])
                ->name('api.chamber.devices.control');

            Route::post('/{deviceCode}/devices/control-batch', [ChamberDeviceController::class, 'controlMultiple'])
                ->name('api.chamber.devices.control-batch');

            // Auto control endpoints
            Route::prefix('{deviceCode}/auto-control')->group(function () {
                Route::get('/', [ChamberAutoControlController::class, 'getConfig'])
                    ->name('api.chamber.auto-control.config');

                Route::put('/{controlType}', [ChamberAutoControlController::class, 'updateConfig'])
                    ->name('api.chamber.auto-control.update');

                Route::post('/{controlType}/control', [ChamberAutoControlController::class, 'manualControl'])
                    ->name('api.chamber.auto-control.manual');

                Route::get('/status', [ChamberAutoControlController::class, 'getDeviceStatus'])
                    ->name('api.chamber.auto-control.status');

                Route::get('/logs', [ChamberAutoControlController::class, 'getLogs'])
                    ->name('api.chamber.auto-control.logs');

                Route::get('/{controlType}/schedules', [ChamberAutoControlController::class, 'getSchedules'])
                    ->name('api.chamber.auto-control.schedules');

                Route::put('/{controlType}/schedules/{scheduleIndex}', [ChamberAutoControlController::class, 'updateSchedule'])
                    ->name('api.chamber.auto-control.schedule.update');
            });
        });
    });
});
