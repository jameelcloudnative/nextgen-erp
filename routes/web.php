<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Activity Log routes
    Route::prefix('api/activity-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\ActivityLogController::class, 'index']);
        Route::get('/user/{userId}', [App\Http\Controllers\ActivityLogController::class, 'userActivities']);
        Route::get('/system', [App\Http\Controllers\ActivityLogController::class, 'systemActivities']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
