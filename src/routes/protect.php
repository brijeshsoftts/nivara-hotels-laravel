<?php

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HousekeepingTaskController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Operations Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Users Administration
Route::get('/dashboard/users', [UserController::class, 'index'])->name('dashboard.users');
Route::post('/dashboard/users', [UserController::class, 'store'])->name('dashboard.users.store');
Route::put('/dashboard/users/{id}', [UserController::class, 'update'])->name('dashboard.users.update');
Route::patch('/dashboard/users/{id}', [UserController::class, 'update'])->name('dashboard.users.patch');
Route::patch('/dashboard/users/{id}/status', [UserController::class, 'toggleStatus'])->name('dashboard.users.status');
Route::delete('/dashboard/users/{id}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');

// Staff Management
Route::get('/dashboard/staff', [StaffController::class, 'index'])->name('dashboard.staff');
Route::post('/dashboard/staff', [StaffController::class, 'store'])->name('dashboard.staff.store');
Route::put('/dashboard/staff/{id}', [StaffController::class, 'update'])->name('dashboard.staff.update');
Route::patch('/dashboard/staff/{id}', [StaffController::class, 'update'])->name('dashboard.staff.patch');
Route::patch('/dashboard/staff/{id}/status', [StaffController::class, 'toggleStatus'])->name('dashboard.staff.status');
Route::delete('/dashboard/staff/{id}', [StaffController::class, 'destroy'])->name('dashboard.staff.destroy');

// Customer Directory
Route::get('/dashboard/customers', [CustomerController::class, 'index'])->name('dashboard.customers');
Route::post('/dashboard/customers', [CustomerController::class, 'store'])->name('dashboard.customers.store');
Route::put('/dashboard/customers/{id}', [CustomerController::class, 'update'])->name('dashboard.customers.update');
Route::patch('/dashboard/customers/{id}', [CustomerController::class, 'update'])->name('dashboard.customers.patch');
Route::delete('/dashboard/customers/{id}', [CustomerController::class, 'destroy'])->name('dashboard.customers.destroy');

// Rooms & Catalog
Route::get('/dashboard/rooms', [RoomController::class, 'index'])->name('dashboard.rooms');
Route::post('/dashboard/rooms', [RoomController::class, 'store'])->name('dashboard.rooms.store');
Route::put('/dashboard/rooms/{id}', [RoomController::class, 'update'])->name('dashboard.rooms.update');
Route::patch('/dashboard/rooms/{id}', [RoomController::class, 'update'])->name('dashboard.rooms.patch');
Route::patch('/dashboard/rooms/{id}/release-out-of-order', [RoomController::class, 'releaseOutOfOrder'])->name('dashboard.rooms.release');
Route::delete('/dashboard/rooms/{id}', [RoomController::class, 'destroy'])->name('dashboard.rooms.destroy');

Route::get('/dashboard/room-types', [RoomController::class, 'index'])->name('dashboard.room-types');
Route::post('/dashboard/room-types', [RoomTypeController::class, 'store'])->name('dashboard.room-types.store');
Route::put('/dashboard/room-types/{id}', [RoomTypeController::class, 'update'])->name('dashboard.room-types.update');
Route::patch('/dashboard/room-types/{id}', [RoomTypeController::class, 'update'])->name('dashboard.room-types.patch');
Route::delete('/dashboard/room-types/{id}', [RoomTypeController::class, 'destroy'])->name('dashboard.room-types.destroy');

Route::get('/dashboard/amenities', [RoomController::class, 'index'])->name('dashboard.amenities');
Route::post('/dashboard/amenities', [AmenityController::class, 'store'])->name('dashboard.amenities.store');
Route::put('/dashboard/amenities/{id}', [AmenityController::class, 'update'])->name('dashboard.amenities.update');
Route::patch('/dashboard/amenities/{id}', [AmenityController::class, 'update'])->name('dashboard.amenities.patch');
Route::delete('/dashboard/amenities/{id}', [AmenityController::class, 'destroy'])->name('dashboard.amenities.destroy');

// Bookings
Route::get('/dashboard/bookings', [BookingController::class, 'index'])->name('dashboard.bookings');
Route::get('/dashboard/bookings/new', [BookingController::class, 'create'])->name('dashboard.bookings.new');
Route::post('/dashboard/bookings', [BookingController::class, 'store'])->name('dashboard.bookings.store');
Route::patch('/dashboard/bookings/{id}/status', [BookingController::class, 'updateStatus'])->name('dashboard.bookings.status');

// Housekeeping Tasks
Route::get('/dashboard/tasks', [HousekeepingTaskController::class, 'index'])->name('dashboard.tasks');
Route::post('/dashboard/tasks', [HousekeepingTaskController::class, 'store'])->name('dashboard.tasks.store');
Route::put('/dashboard/tasks/{id}', [HousekeepingTaskController::class, 'update'])->name('dashboard.tasks.update');
Route::patch('/dashboard/tasks/{id}', [HousekeepingTaskController::class, 'update'])->name('dashboard.tasks.patch');
Route::patch('/dashboard/tasks/{id}/status', [HousekeepingTaskController::class, 'updateStatus'])->name('dashboard.tasks.status');
Route::delete('/dashboard/tasks/{id}', [HousekeepingTaskController::class, 'destroy'])->name('dashboard.tasks.destroy');

// Issues
Route::get('/dashboard/issues', [IssueController::class, 'index'])->name('dashboard.issues');
Route::post('/dashboard/issues', [IssueController::class, 'store'])->name('dashboard.issues.store');
Route::put('/dashboard/issues/{id}', [IssueController::class, 'update'])->name('dashboard.issues.update');
Route::patch('/dashboard/issues/{id}', [IssueController::class, 'update'])->name('dashboard.issues.patch');
Route::delete('/dashboard/issues/{id}', [IssueController::class, 'destroy'])->name('dashboard.issues.destroy');

// Invoices
Route::get('/dashboard/invoices', [InvoiceController::class, 'index'])->name('dashboard.invoices');
Route::patch('/dashboard/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('dashboard.invoices.cancel');

// Payments
Route::get('/dashboard/payments', [PaymentController::class, 'index'])->name('dashboard.payments');
Route::post('/dashboard/payments/cash', [PaymentController::class, 'storeCash'])->name('dashboard.payments.cash');
Route::post('/dashboard/payments/qr', [PaymentController::class, 'storeQr'])->name('dashboard.payments.qr');

// Inquiries
Route::get('/dashboard/inquiries', [InquiryController::class, 'index'])->name('dashboard.inquiries');
Route::delete('/dashboard/inquiries/{id}', [InquiryController::class, 'destroy'])->name('dashboard.inquiries.destroy');

// Audit Logs
Route::get('/dashboard/audit-logs', [AuditLogController::class, 'index'])->name('dashboard.audit-logs');

// User Profile
Route::get('/dashboard/profile', [ProfileController::class, 'edit'])->name('dashboard.profile');
Route::patch('/dashboard/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');
Route::patch('/dashboard/profile/change-password', [ProfileController::class, 'changePassword'])->name('dashboard.profile.password');
