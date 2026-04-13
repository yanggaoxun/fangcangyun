<?php

use App\Http\Controllers\Api\AuthController;
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

});
