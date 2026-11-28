<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/available-rooms', [BookingController::class, 'availableRooms'])
    ->name('available-rooms');
Route::get('/public/bookings/available-rooms', [BookingController::class, 'availableRooms'])
    ->name('public.bookings.available-rooms');
Route::get('/storage/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path));
})->where('path', '.*')->name('public.storage');
Route::post('/payments/public/orders', [PaymentController::class, 'createPublicOrder'])->name('payments.public.orders');
Route::post('/payments/public/verify', [PaymentController::class, 'verifyPublicPayment'])->name('payments.public.verify');
Route::post('/payments/public/status', [PaymentController::class, 'publicPaymentStatus'])->name('payments.public.status');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('payments.webhook');

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
