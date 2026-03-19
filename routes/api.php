<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\OutletApiController;
use App\Http\Controllers\Api\CheckInApiController;
use App\Http\Controllers\Api\SyncApiController;
use App\Http\Controllers\Api\PlannedVisitApiController;
use App\Http\Controllers\Api\DcrApiController;
use App\Http\Controllers\Api\AuditTemplateApiController;
use App\Http\Controllers\Api\AuditRunApiController;
use App\Http\Controllers\Api\AuditReportApiController;

/*
|--------------------------------------------------------------------------
| Distribution mobile API (Phase 5)
|--------------------------------------------------------------------------
| Token auth via Laravel Sanctum. Mobile app uses these for check-in,
| outlets list, and offline sync.
*/

Route::post('/login', [AuthApiController::class, 'login']);

// Verify/resend OTP: public routes; controller validates Bearer or body pending_token (avoids 404 when auth middleware runs before route)
Route::post('/verify-otp', [AuthApiController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthApiController::class, 'resendOtp']);

Route::middleware(['auth:sanctum', 'ability:full'])->group(function () {
    Route::get('/user', [AuthApiController::class, 'user']);
    Route::get('/dashboard-summary', [DashboardApiController::class, 'summary']);
    Route::get('/outlets', [OutletApiController::class, 'index']);
    Route::get('/outlets/{id}', [OutletApiController::class, 'show']);
    Route::post('/outlets', [OutletApiController::class, 'store']);
    Route::put('/outlets/{id}', [OutletApiController::class, 'update']);
    Route::post('/check-ins', [CheckInApiController::class, 'store']);
    Route::post('/check-ins/{id}/check-out', [CheckInApiController::class, 'checkOut']);
    Route::post('/sync/check-ins', [SyncApiController::class, 'syncCheckIns']);

    Route::get('/planned-visits', [PlannedVisitApiController::class, 'index']);
    Route::post('/planned-visits', [PlannedVisitApiController::class, 'store']);
    Route::delete('/planned-visits/{plannedVisit}', [PlannedVisitApiController::class, 'destroy']);

    Route::get('/dcr', [DcrApiController::class, 'index']);

    Route::get('/audit-templates', [AuditTemplateApiController::class, 'index']);
    Route::get('/audit-templates/{auditTemplate}', [AuditTemplateApiController::class, 'show']);
    Route::post('/audit-runs', [AuditRunApiController::class, 'store']);
    Route::post('/audit-runs/{auditRun}/submit', [AuditRunApiController::class, 'submit']);
    Route::get('/audit-runs/{auditRun}', [AuditRunApiController::class, 'show']);
    Route::get('/audit-reports', [AuditReportApiController::class, 'index']);
});
