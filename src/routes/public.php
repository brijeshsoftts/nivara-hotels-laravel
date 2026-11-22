<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\PublicCheckoutController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\PublicRoomController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/rooms', [PublicRoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms-list', [PublicRoomController::class, 'index'])->name('rooms');
Route::get('/rooms/{slug}', [PublicRoomController::class, 'show'])->name('rooms.show');
Route::get('/contact', [PublicContactController::class, 'index'])->name('contact');
Route::post('/contact', [PublicContactController::class, 'store'])->name('contact.store');
Route::get('/checkout', [PublicCheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [PublicCheckoutController::class, 'store'])->name('checkout.store');
Route::get('/rooms/{slug}/book', [PublicCheckoutController::class, 'index'])->name('rooms.book');
