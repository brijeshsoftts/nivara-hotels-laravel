<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/public.php';
require __DIR__ . '/auth.php';

Route::get('/', function () {
    return view('pages.public.index');
})->name('home');
