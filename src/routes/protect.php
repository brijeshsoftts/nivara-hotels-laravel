<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/dashboard/users', function () {
    return Inertia::render('protect/users/index');
})->name('dashboard.users');

Route::get('/dashboard/staff', function () {
    return Inertia::render('protect/staff/index');
})->name('dashboard.staff');

Route::get('/dashboard/customers', function () {
    return Inertia::render('protect/customers/index');
})->name('dashboard.customers');

Route::get('/dashboard/rooms', function () {
    return Inertia::render('protect/rooms/index');
})->name('dashboard.rooms');

Route::get('/dashboard/room-types', function () {
    return Inertia::render('protect/rooms/index');
})->name('dashboard.room-types');

Route::get('/dashboard/amenities', function () {
    return Inertia::render('protect/rooms/index');
})->name('dashboard.amenities');

Route::get('/dashboard/bookings', function () {
    return Inertia::render('protect/bookings/index');
})->name('dashboard.bookings');

Route::get('/dashboard/bookings/new', function () {
    return Inertia::render('protect/bookings/new');
})->name('dashboard.bookings.new');

Route::get('/dashboard/tasks', function () {
    return Inertia::render('protect/tasks/index');
})->name('dashboard.tasks');

Route::get('/dashboard/issues', function () {
    return Inertia::render('protect/issues/index');
})->name('dashboard.issues');

Route::get('/dashboard/invoices', function () {
    return Inertia::render('protect/invoices/index');
})->name('dashboard.invoices');

Route::get('/dashboard/payments', function () {
    return Inertia::render('protect/payments/index');
})->name('dashboard.payments');

Route::get('/dashboard/inquiries', function () {
    return Inertia::render('protect/inquiries/index');
})->name('dashboard.inquiries');

Route::get('/dashboard/audit-logs', function () {
    return Inertia::render('protect/audit-logs/index');
})->name('dashboard.audit-logs');

Route::get('/dashboard/profile', function () {
    return Inertia::render('protect/profile/index');
})->name('dashboard.profile');
