<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

require __DIR__ . '/public.php';

Route::middleware('auth')->group(function () {
    require __DIR__ . '/protect.php';
});

Route::get('/', function () {
    // return view('pages.public.index');
    return Inertia::render('welcome');
})->name('home');
