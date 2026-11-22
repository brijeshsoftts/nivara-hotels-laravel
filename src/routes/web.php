<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

require __DIR__ . '/public.php';

Route::middleware('auth')->group(function () {
    require __DIR__ . '/protect.php';
});

// API endpoints for frontend hooks (TanStack Query / Axios)
require __DIR__ . '/api.php';

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');
