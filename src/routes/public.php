<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/about', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'storeContact')->name('contact.store');

    Route::get('/rooms', 'rooms')->name('rooms.index');
    Route::get('/rooms-list', 'rooms')->name('rooms');
    Route::get('/rooms/{slug}', 'roomDetails')->name('rooms.show');
    Route::get('/checkout', 'checkout')->name('checkout');
    Route::post('/checkout', 'storeCheckout')->name('checkout.store');
    Route::get('/rooms/{slug}/book', 'checkout')->name('rooms.book');

    // Compatibility endpoints for public API / tests
    Route::get('/public/rooms', 'roomsApi')->name('public.rooms');
    Route::get('/public/amenities', 'amenitiesApi')->name('public.amenities');
    Route::post('/public/contact', 'storeContact')->name('public.contact');
});
