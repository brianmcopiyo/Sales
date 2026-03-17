<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\OutletApiController;
use App\Http\Controllers\Api\CheckInApiController;
use App\Http\Controllers\Api\SyncApiController;

/*
|--------------------------------------------------------------------------
| Distribution mobile API (Phase 5)
|--------------------------------------------------------------------------
| Token auth via Laravel Sanctum. Mobile app uses these for check-in,
| outlets list, and offline sync.
*/

Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/outlets', [OutletApiController::class, 'index']);
    Route::get('/outlets/{id}', [OutletApiController::class, 'show']);
    Route::post('/outlets', [OutletApiController::class, 'store']);
    Route::put('/outlets/{id}', [OutletApiController::class, 'update']);
    Route::post('/check-ins', [CheckInApiController::class, 'store']);
    Route::post('/sync/check-ins', [SyncApiController::class, 'syncCheckIns']);
});
