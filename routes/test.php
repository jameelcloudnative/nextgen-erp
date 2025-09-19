<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-company-direct', function () {
    try {
        // Test if our middleware class exists and is loadable
        $middleware = new App\Http\Middleware\CompanyContextMiddleware();

        return response()->json([
            'status' => 'success',
            'message' => 'CompanyContextMiddleware class loaded successfully',
            'middleware_class' => get_class($middleware),
            'helper_functions' => [
                'currentCompany_exists' => function_exists('currentCompany'),
                'setCurrentCompany_exists' => function_exists('setCurrentCompany'),
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
});
