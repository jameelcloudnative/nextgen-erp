<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Simple middleware test route (no auth required)
Route::get('/test-middleware', function () {
    return response()->json([
        'message' => 'Middleware test route working',
        'middlewares' => request()->route()->gatherMiddleware(),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Test route for company context middleware
    Route::middleware(['company.context'])->group(function () {
        Route::get('/test-company-context', function () {
            return response()->json([
                'current_company' => currentCompany(),
                'user_companies' => auth()->user()->companies->pluck('name', 'id'),
                'message' => 'Company context middleware is working!'
            ]);
        });
    });

    // Activity Log routes
    Route::prefix('api/activity-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\ActivityLogController::class, 'index']);
        Route::get('/user/{userId}', [App\Http\Controllers\ActivityLogController::class, 'userActivities']);
        Route::get('/system', [App\Http\Controllers\ActivityLogController::class, 'systemActivities']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
