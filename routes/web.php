<?php

use App\Http\Controllers\{AccountController, ChecklistController, AssetController, AuthController, DashboardController, MaintenanceController, ReportController, SecurityAuditController, UserController};
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
// BUG B-11 [OPEN]: throttle is keyed by IP only. Many users behind one IP lock each other out, and one account can be
//   attacked from many IPs. FIX: RateLimiter::for('login') keyed by lowercase email + IP. (Also see B-10.)
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1']);

// 'active' signs out accounts that were deactivated while logged in; role gates and policies sit on top of it.
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/account/password', [AccountController::class, 'edit'])->name('account.password');
    Route::put('/account/password', [AccountController::class, 'update'])->name('account.password.update');
    Route::get('/my-findings', [SecurityAuditController::class, 'mine'])->name('findings.mine');

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
        Route::get('/checklists', [ChecklistController::class, 'index'])->name('checklists.index');
        Route::get('/checklists/create', [ChecklistController::class, 'create'])->name('checklists.create');
        Route::post('/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
        // Edit/delete: creator or admin (ChecklistTemplatePolicy).
        Route::get('/checklists/{template}/edit', [ChecklistController::class, 'edit'])->name('checklists.edit');
        Route::put('/checklists/{template}', [ChecklistController::class, 'update'])->name('checklists.update');
        Route::delete('/checklists/{template}', [ChecklistController::class, 'destroy'])->name('checklists.destroy');
        Route::patch('/checks/{check}', [SecurityAuditController::class, 'updateCheck'])->name('checks.update');
        Route::post('/audits/{audit}/findings', [SecurityAuditController::class, 'storeFinding'])->name('findings.store');
        Route::get('/reports/findings.csv', [ReportController::class, 'findingsCsv'])->name('reports.findings');
    });
    // Remediation owners (any role) may start work on / resolve only findings assigned to them (AuditFindingPolicy).
    Route::patch('/findings/{finding}', [SecurityAuditController::class, 'updateFinding'])->name('findings.update');
    // Choosing the remediation owner / due date: the audit's auditor or admin (AuditFindingPolicy::assign).
    Route::patch('/findings/{finding}/assignment', [SecurityAuditController::class, 'assignFinding'])->name('findings.assign');

    // Administration
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::get('/reports/logs', [ReportController::class, 'logs'])->name('reports.logs');
    });
});
