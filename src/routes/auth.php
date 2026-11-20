<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('pages.auth.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.auth.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('pages.auth.forgot-password');
})->name('forgot-password');

Route::get('/reset-password', function () {
    return view('pages.auth.reset-password');
})->name('reset-password');
