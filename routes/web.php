<?php

use App\Http\Controllers\{AssetController, AuthController, DashboardController, MaintenanceController, ReportController, SecurityAuditController, UserController};
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Assets: everyone reads, admin writes (create/edit routes are declared before {asset}).
    Route::middleware('role:admin')->group(function () {
        Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    });
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

    // Maintenance (record-level visibility is enforced in the controller).
    Route::middleware('role:technician,staff')->group(function () {
        Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/maintenance/{maintenance}', [MaintenanceController::class, 'show'])->name('maintenance.show');
        Route::post('/maintenance/{maintenance}/transition', [MaintenanceController::class, 'transition'])->name('maintenance.transition');
    });

    // Security audits
    Route::middleware('role:auditor')->group(function () {
        Route::get('/audits', [SecurityAuditController::class, 'index'])->name('audits.index');
        Route::get('/audits/create', [SecurityAuditController::class, 'create'])->name('audits.create');
        Route::post('/audits', [SecurityAuditController::class, 'store'])->name('audits.store');
        Route::get('/audits/{audit}', [SecurityAuditController::class, 'show'])->name('audits.show');
        Route::post('/audits/{audit}/complete', [SecurityAuditController::class, 'complete'])->name('audits.complete');
        Route::post('/audits/{audit}/findings', [SecurityAuditController::class, 'storeFinding'])->name('findings.store');
        Route::get('/reports/findings.csv', [ReportController::class, 'findingsCsv'])->name('reports.findings');
    });
    // Remediation owners (any role) may update only findings assigned to them (checked in controller).
    Route::patch('/findings/{finding}', [SecurityAuditController::class, 'updateFinding'])->name('findings.update');

    // Administration
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::get('/reports/logs', [ReportController::class, 'logs'])->name('reports.logs');
    });
});
