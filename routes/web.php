<?php

use Illuminate\Support\Facades\Route;
use Abdalmolood\AiSecurityGuardian\Http\Controllers\DashboardController;
use Abdalmolood\AiSecurityGuardian\Http\Controllers\AssetController;
use Abdalmolood\AiSecurityGuardian\Http\Middleware\AuthorizeAiSecurity;
use Abdalmolood\AiSecurityGuardian\Http\Middleware\SetAiSecurityLocale;

if (config('ai-security-guardian.ui.enabled', true)) {
    $middleware = array_values(array_unique(array_merge(
        ['web', SetAiSecurityLocale::class],
        config('ai-security-guardian.ui.middleware', ['web', 'auth']),
        [AuthorizeAiSecurity::class]
    )));

    Route::middleware($middleware)
        ->prefix(config('ai-security-guardian.ui.prefix', 'ai-security'))
        ->name('ai-security.')
        ->group(function () {
            Route::get('/assets/app.js', [AssetController::class, 'js'])->name('assets.js');

            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::match(['get', 'post'], '/scan', [DashboardController::class, 'scan'])->name('scan');

            Route::get('/scans', [DashboardController::class, 'scans'])->name('scans.index');
            Route::get('/scans/{scan}', [DashboardController::class, 'showScan'])->name('scans.show');
            Route::get('/scans/{scan}/report.{format}', [DashboardController::class, 'downloadScanReport'])->name('scans.report');

            Route::get('/findings', [DashboardController::class, 'findings'])->name('findings.index');
            Route::get('/findings/{finding}', [DashboardController::class, 'showFinding'])->name('findings.show');
            Route::post('/findings/{finding}/status', [DashboardController::class, 'updateFindingStatus'])->name('findings.status');

            Route::get('/reports', [DashboardController::class, 'reports'])->name('reports.index');
            Route::post('/reports/generate', [DashboardController::class, 'generateReport'])->name('reports.generate');
            Route::get('/reports/latest.{format}', [DashboardController::class, 'downloadLatestReport'])->name('reports.download');

            Route::get('/patches', [DashboardController::class, 'patches'])->name('patches.index');
            Route::get('/patches/{patch}/download', [DashboardController::class, 'downloadPatch'])->name('patches.download');

            Route::get('/settings/providers', [DashboardController::class, 'providers'])->name('settings.providers');
            Route::get('/settings/scanners', [DashboardController::class, 'scanners'])->name('settings.scanners');
            Route::get('/settings/notifications', [DashboardController::class, 'notifications'])->name('settings.notifications');

            Route::get('/health', [DashboardController::class, 'health'])->name('health');
            Route::get('/help', [DashboardController::class, 'help'])->name('help');
        });
}
