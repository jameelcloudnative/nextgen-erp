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

    // Company-scoped routes (require company context)
    Route::middleware(['company.context'])->group(function () {

        // User Management Routes
        Route::get('users/available', [App\Http\Controllers\UserManagementController::class, 'available'])
            ->name('users.available');
        Route::resource('users', App\Http\Controllers\UserManagementController::class);

        // User Role Management API Routes
        Route::prefix('api/user-roles')->name('user-roles.')->group(function () {
            Route::post('{user}/assign-company', [App\Http\Controllers\UserRoleController::class, 'assignToCompany'])
                ->name('assign-company');
            Route::put('{user}/role', [App\Http\Controllers\UserRoleController::class, 'updateRole'])
                ->name('update-role');
            Route::delete('{user}/company', [App\Http\Controllers\UserRoleController::class, 'removeFromCompany'])
                ->name('remove-company');
            Route::put('{user}/default-company', [App\Http\Controllers\UserRoleController::class, 'setDefaultCompany'])
                ->name('set-default-company');
            Route::get('{user}', [App\Http\Controllers\UserRoleController::class, 'getRoles'])
                ->name('get-roles');
        });

        // Company switching endpoint
        Route::post('api/switch-company', [App\Http\Controllers\UserRoleController::class, 'switchCompany'])
            ->name('switch-company');

        // Test route for company context middleware
        Route::get('/test-company-context', function () {
            return response()->json([
                'current_company' => currentCompany(),
                'user_companies' => auth()->user()->companies->pluck('name', 'id'),
                'message' => 'Company context middleware is working!'
            ]);
        });
    });

    // Activity Log routes (non-company specific)
    Route::prefix('api/activity-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\ActivityLogController::class, 'index']);
        Route::get('/user/{userId}', [App\Http\Controllers\ActivityLogController::class, 'userActivities']);
        Route::get('/system', [App\Http\Controllers\ActivityLogController::class, 'systemActivities']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
