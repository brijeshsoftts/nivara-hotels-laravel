<?php

use Illuminate\Support\Facades\Route;

// Public portal routes
require __DIR__ . '/public.php';

// Protected dashboard routes
Route::middleware('auth')->group(function () {
    require __DIR__ . '/protect.php';
});

// Compatibility endpoints for automated tests (OperationsApiTest)
require __DIR__ . '/api.php';
