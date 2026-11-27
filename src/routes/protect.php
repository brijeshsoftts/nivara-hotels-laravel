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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::patch('/dashboard/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('dashboard.notifications.read');
    Route::patch('/dashboard/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('dashboard.notifications.read-all');

    // 1. Dashboard Pages & Analytics Endpoints
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/room-status', [DashboardController::class, 'roomStatus'])->name('dashboard.room-status');
    Route::get('/dashboard/revenue-trend', [DashboardController::class, 'revenueTrend'])->name('dashboard.revenue-trend');
    Route::get('/dashboard/booking-status', [DashboardController::class, 'bookingStatus'])->name('dashboard.booking-status');
    Route::get('/dashboard/stays', [DashboardController::class, 'stays'])->name('dashboard.stays');

    // 2. Profile Management
    Route::get('/profile/me', [ProfileController::class, 'me'])->name('profile.me');
    Route::get('/profile/details', [ProfileController::class, 'details'])->name('profile.details');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.delete');
    Route::get('/dashboard/profile', [ProfileController::class, 'edit'])->name('dashboard.profile');
    Route::patch('/dashboard/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');
    Route::patch('/dashboard/profile/change-password', [ProfileController::class, 'changePassword'])->name('dashboard.profile.password');
    Route::post('/dashboard/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('dashboard.profile.avatar.update');
    Route::delete('/dashboard/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('dashboard.profile.avatar.delete');
    Route::patch('/profile/me', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    // 3. Users Administration
    Route::get('/dashboard/users', [UserController::class, 'index'])->name('dashboard.users');
    Route::post('/dashboard/users', [UserController::class, 'store'])->name('dashboard.users.store');
    Route::put('/dashboard/users/{id}', [UserController::class, 'update'])->name('dashboard.users.update');
    Route::patch('/dashboard/users/{id}', [UserController::class, 'update'])->name('dashboard.users.patch');
    Route::patch('/dashboard/users/{id}/status', [UserController::class, 'toggleStatus'])->name('dashboard.users.status');
    Route::delete('/dashboard/users/{id}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');
    Route::get('/users/stats', [UserController::class, 'stats'])->name('users.stats');
    Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    Route::resource('users', UserController::class);

    // 4. Staff Management
    Route::get('/dashboard/staff', [StaffController::class, 'index'])->name('dashboard.staff');
    Route::post('/dashboard/staff', [StaffController::class, 'store'])->name('dashboard.staff.store');
    Route::put('/dashboard/staff/{id}', [StaffController::class, 'update'])->name('dashboard.staff.update');
    Route::patch('/dashboard/staff/{id}', [StaffController::class, 'update'])->name('dashboard.staff.patch');
    Route::patch('/dashboard/staff/{id}/status', [StaffController::class, 'toggleStatus'])->name('dashboard.staff.status');
    Route::delete('/dashboard/staff/{id}', [StaffController::class, 'destroy'])->name('dashboard.staff.destroy');
    Route::get('/staff/stats', [StaffController::class, 'stats'])->name('staff.stats');
    Route::patch('/staff/{id}/status', [StaffController::class, 'toggleStatus'])->name('staff.status');
    Route::resource('staff', StaffController::class);

    // 5. Customer Directory
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/stats', [CustomerController::class, 'stats'])->name('customers.stats');
    Route::get('/customers/{id}/bookings', [CustomerController::class, 'customerBookings'])->name('customers.bookings');
    Route::get('/customers/{id}/payments', [CustomerController::class, 'customerPayments'])->name('customers.payments');
    Route::get('/dashboard/customers', [CustomerController::class, 'index'])->name('dashboard.customers');
    Route::post('/dashboard/customers', [CustomerController::class, 'store'])->name('dashboard.customers.store');
    Route::put('/dashboard/customers/{id}', [CustomerController::class, 'update'])->name('dashboard.customers.update');
    Route::patch('/dashboard/customers/{id}', [CustomerController::class, 'update'])->name('dashboard.customers.patch');
    Route::patch('/dashboard/customers/{id}/status', [CustomerController::class, 'toggleStatus'])->name('dashboard.customers.status');
    Route::delete('/dashboard/customers/{id}', [CustomerController::class, 'destroy'])->name('dashboard.customers.destroy');
    Route::patch('/customers/{id}/status', [CustomerController::class, 'toggleStatus'])->name('customers.status');
    Route::resource('customers', CustomerController::class);

    // 6. Rooms & Catalog
    Route::get('/dashboard/rooms', [RoomController::class, 'index'])->name('dashboard.rooms');
    Route::post('/dashboard/rooms', [RoomController::class, 'store'])->name('dashboard.rooms.store');
    Route::put('/dashboard/rooms/{id}', [RoomController::class, 'update'])->name('dashboard.rooms.update');
    Route::patch('/dashboard/rooms/{id}', [RoomController::class, 'update'])->name('dashboard.rooms.patch');
    Route::patch('/dashboard/rooms/{id}/release-out-of-order', [RoomController::class, 'releaseOutOfOrder'])->name('dashboard.rooms.release');
    Route::delete('/dashboard/rooms/{id}', [RoomController::class, 'destroy'])->name('dashboard.rooms.destroy');
    Route::get('/rooms/stats', [RoomController::class, 'stats'])->name('rooms.stats');
    Route::patch('/rooms/{id}/release-out-of-order', [RoomController::class, 'releaseOutOfOrder'])->name('rooms.release');
    Route::resource('rooms', RoomController::class)->except(['index'])->names('protect.rooms');

    Route::get('/dashboard/room-types', [RoomController::class, 'index'])->name('dashboard.room-types');
    Route::post('/dashboard/room-types', [RoomTypeController::class, 'store'])->name('dashboard.room-types.store');
    Route::put('/dashboard/room-types/{id}', [RoomTypeController::class, 'update'])->name('dashboard.room-types.update');
    Route::patch('/dashboard/room-types/{id}', [RoomTypeController::class, 'update'])->name('dashboard.room-types.patch');
    Route::patch('/dashboard/room-types/{id}/status', [RoomTypeController::class, 'toggleStatus'])->name('dashboard.room-types.status');
    Route::delete('/dashboard/room-types/{id}', [RoomTypeController::class, 'destroy'])->name('dashboard.room-types.destroy');
    Route::patch('/room-types/{id}/status', [RoomTypeController::class, 'toggleStatus'])->name('room-types.status');
    Route::resource('room-types', RoomTypeController::class);

    Route::get('/dashboard/amenities', [RoomController::class, 'index'])->name('dashboard.amenities');
    Route::post('/dashboard/amenities', [AmenityController::class, 'store'])->name('dashboard.amenities.store');
    Route::put('/dashboard/amenities/{id}', [AmenityController::class, 'update'])->name('dashboard.amenities.update');
    Route::patch('/dashboard/amenities/{id}', [AmenityController::class, 'update'])->name('dashboard.amenities.patch');
    Route::patch('/dashboard/amenities/{id}/status', [AmenityController::class, 'toggleStatus'])->name('dashboard.amenities.status');
    Route::delete('/dashboard/amenities/{id}', [AmenityController::class, 'destroy'])->name('dashboard.amenities.destroy');
    Route::patch('/amenities/{id}/status', [AmenityController::class, 'toggleStatus'])->name('amenities.status');
    Route::resource('amenities', AmenityController::class);

    // 7. Bookings
    Route::get('/dashboard/bookings', [BookingController::class, 'index'])->name('dashboard.bookings');
    Route::get('/dashboard/bookings/new', [BookingController::class, 'create'])->name('dashboard.bookings.new');
    Route::post('/dashboard/bookings', [BookingController::class, 'store'])->name('dashboard.bookings.store');
    Route::patch('/dashboard/bookings/{id}/status', [BookingController::class, 'updateStatus'])->name('dashboard.bookings.status');
    Route::get('/bookings/stats', [BookingController::class, 'stats'])->name('bookings.stats');
    Route::get('/bookings/available-rooms', [BookingController::class, 'availableRooms'])->name('bookings.available-rooms');
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::resource('bookings', BookingController::class);

    // 8. Housekeeping Tasks
    Route::get('/dashboard/tasks', [HousekeepingTaskController::class, 'index'])->name('dashboard.tasks');
    Route::post('/dashboard/tasks', [HousekeepingTaskController::class, 'store'])->name('dashboard.tasks.store');
    Route::put('/dashboard/tasks/{id}', [HousekeepingTaskController::class, 'update'])->name('dashboard.tasks.update');
    Route::patch('/dashboard/tasks/{id}', [HousekeepingTaskController::class, 'update'])->name('dashboard.tasks.patch');
    Route::patch('/dashboard/tasks/{id}/status', [HousekeepingTaskController::class, 'updateStatus'])->name('dashboard.tasks.status');
    Route::delete('/dashboard/tasks/{id}', [HousekeepingTaskController::class, 'destroy'])->name('dashboard.tasks.destroy');
    Route::get('/tasks/stats', [HousekeepingTaskController::class, 'stats'])->name('tasks.stats');
    Route::patch('/tasks/{id}/status', [HousekeepingTaskController::class, 'updateStatus'])->name('tasks.status');
    Route::resource('tasks', HousekeepingTaskController::class);

    // 9. Issues
    Route::get('/dashboard/issues', [IssueController::class, 'index'])->name('dashboard.issues');
    Route::post('/dashboard/issues', [IssueController::class, 'store'])->name('dashboard.issues.store');
    Route::put('/dashboard/issues/{id}', [IssueController::class, 'update'])->name('dashboard.issues.update');
    Route::patch('/dashboard/issues/{id}', [IssueController::class, 'update'])->name('dashboard.issues.patch');
    Route::delete('/dashboard/issues/{id}', [IssueController::class, 'destroy'])->name('dashboard.issues.destroy');
    Route::get('/issues/stats', [IssueController::class, 'stats'])->name('issues.stats');
    Route::resource('issues', IssueController::class);

    // 10. Invoices
    Route::get('/dashboard/invoices', [InvoiceController::class, 'index'])->name('dashboard.invoices');
    Route::patch('/dashboard/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('dashboard.invoices.cancel');
    Route::get('/invoices/stats', [InvoiceController::class, 'stats'])->name('invoices.stats');
    Route::post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel.post');
    Route::patch('/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::resource('invoices', InvoiceController::class);

    // 11. Payments
    Route::get('/dashboard/payments', [PaymentController::class, 'index'])->name('dashboard.payments');
    Route::post('/dashboard/payments/cash', [PaymentController::class, 'storeCash'])->name('dashboard.payments.cash');
    Route::post('/dashboard/payments/qr', [PaymentController::class, 'storeQr'])->name('dashboard.payments.qr');
    Route::post('/payments/orders', [PaymentController::class, 'createOrder'])->name('payments.orders');
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment'])->name('payments.verify');
    Route::get('/payments/stats', [PaymentController::class, 'stats'])->name('payments.stats');
    Route::post('/payments/cash', [PaymentController::class, 'storeCash'])->name('payments.cash');
    Route::post('/payments/qr', [PaymentController::class, 'storeQr'])->name('payments.qr');
    Route::post('/payments/{id}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::resource('payments', PaymentController::class);

    // 12. Inquiries
    Route::get('/dashboard/inquiries', [InquiryController::class, 'index'])->name('dashboard.inquiries');
    Route::delete('/dashboard/inquiries/{id}', [InquiryController::class, 'destroy'])->name('dashboard.inquiries.destroy');
    Route::resource('inquiries', InquiryController::class)->only(['index', 'destroy']);

    // 13. Audit Logs
    Route::get('/dashboard/audit-logs', [AuditLogController::class, 'index'])->name('dashboard.audit-logs');
    Route::resource('audit-logs', AuditLogController::class)->only(['index']);
});
