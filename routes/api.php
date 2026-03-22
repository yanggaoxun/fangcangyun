<?php

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

Route::prefix('v1')->group(function () {
    // Environment data endpoints for chambers
    Route::prefix('environment-data')->group(function () {
        // Single data point submission
        Route::post('/', [EnvironmentDataController::class, 'store'])
            ->name('api.environment-data.store');

        // Batch submission for multiple data points
        Route::post('/batch', [EnvironmentDataController::class, 'batchStore'])
            ->name('api.environment-data.batch');
    });
});
