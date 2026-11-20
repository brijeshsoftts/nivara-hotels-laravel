<?php

use Illuminate\Support\Facades\Route;;

Route::get('/about', function () {
    return view('pages.public.about');
})->name('about');

Route::get('/rooms', function () {
    return view('pages.public.rooms');
})->name('rooms.index');

Route::get('/contact', function () {
    return view('pages.public.contact');
})->name('contact');
