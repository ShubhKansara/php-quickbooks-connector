<?php

use Illuminate\Support\Facades\Route;
use ShubhKansara\PhpQuickbooksConnector\Http\Controllers\QbEntityController;
use ShubhKansara\PhpQuickbooksConnector\Http\Controllers\QuickBooksController;
use ShubhKansara\PhpQuickbooksConnector\Http\Controllers\SyncMonitorController;

Route::get('qbwc', function () {
    // Serve the WSDL file
    return response()->file(
        __DIR__.'/../src/Wsdl/QuickBooksConnector.wsdl',
        ['Content-Type' => 'text/xml']
    );
});

Route::any('/qbwc', [QuickBooksController::class, 'handle']);

Route::prefix('admin/quickbooks/sync-monitor')->group(function () {
    Route::get('/', [SyncMonitorController::class, 'index'])->name('qb.sync.monitor');
    Route::get('/logs', [\ShubhKansara\PhpQuickbooksConnector\Http\Controllers\SyncLogController::class, 'index'])
        ->name('qb.sync.monitor.logs');
    Route::get('/{id}', [SyncMonitorController::class, 'show'])->name('qb.sync.monitor.show');
    Route::post('/{id}/restart', [\ShubhKansara\PhpQuickbooksConnector\Http\Controllers\SyncMonitorController::class, 'restart'])->name('qb.sync.monitor.restart');
});

Route::prefix('admin/quickbooks')->group(function () {
    Route::get('/', function () {
        return view('php-quickbooks::admin.dashboard');
    })->name('qb.admin.dashboard');
    Route::resource('qb-entities', QbEntityController::class);
    Route::get('qb-entities/{qb_entity}/edit', [QbEntityController::class, 'edit'])->name('qb-entities.edit');
});
