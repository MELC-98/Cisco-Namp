<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\InterfaceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ============================================================
// Public Routes
// ============================================================

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute

// ============================================================
// Authenticated Routes
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // --- Dashboard ---
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/devices', [DashboardController::class, 'devices']);
    Route::get('/dashboard/recent-jobs', [DashboardController::class, 'recentJobs']);
    Route::get('/dashboard/recent-backups', [DashboardController::class, 'recentBackups']);

    // --- Devices ---
    Route::apiResource('devices', DeviceController::class);
    Route::post('/devices/{device}/test', [DeviceController::class, 'test']);
    Route::post('/devices/{device}/discover', [DeviceController::class, 'discover']);
    Route::get('/sites', [DeviceController::class, 'sites']);

    // --- Interfaces ---
    Route::get('/devices/{device}/interfaces', [InterfaceController::class, 'index']);
    Route::post('/devices/{device}/interfaces/refresh', [InterfaceController::class, 'refresh']);
    Route::post('/devices/{device}/interfaces/preview', [InterfaceController::class, 'preview']);
    Route::post('/devices/{device}/interfaces/deploy', [InterfaceController::class, 'deploy']);

    // --- Commands & Configs ---
    Route::get('/commands/available', [CommandController::class, 'available']);
    Route::post('/devices/{device}/commands', [CommandController::class, 'execute']);
    Route::get('/devices/{device}/commands', [CommandController::class, 'history']);
    Route::post('/devices/{device}/configure-raw', [CommandController::class, 'configureRaw']);

    // --- Backups ---
    Route::get('/backups', [BackupController::class, 'index']);
    Route::post('/devices/{device}/backups', [BackupController::class, 'store']);
    Route::get('/backups/{backup}', [BackupController::class, 'show']);
    Route::get('/backups/{backup}/download', [BackupController::class, 'download']);
    Route::get('/backups/{backup}/compare/{backup2}', [BackupController::class, 'compare']);

    // --- Jobs ---
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);

    // --- Admin ---
    Route::prefix('admin')->group(function () {
        // Users
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

        // Roles
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);

        // Audit Logs (read-only)
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
