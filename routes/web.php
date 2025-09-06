<?php

use App\Http\Controllers\LogMonitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('log-monitor')->group(function () {
    Route::get('/', [LogMonitorController::class, 'index']);
    Route::get('/logs', [LogMonitorController::class, 'getLogs']);
    Route::get('/filters', [LogMonitorController::class, 'getFilters']);
    Route::get('/test', [LogMonitorController::class, 'createTestLog']);
});
