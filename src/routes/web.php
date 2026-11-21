<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/public.php';

Route::middleware('auth')->group(function () {
    require __DIR__ . '/protect.php';
});

Route::get('/', function () {
    return view('pages.public.index');
})->name('home');
