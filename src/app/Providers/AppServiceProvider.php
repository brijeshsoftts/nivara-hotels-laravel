<?php

namespace App\Providers;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\HousekeepingTask;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use App\Observers\AuditLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (
            [
                Amenity::class,
                Booking::class,
                Customer::class,
                HousekeepingTask::class,
                Inquiry::class,
                Invoice::class,
                Issue::class,
                Payment::class,
                Room::class,
                RoomType::class,
                Staff::class,
                User::class,
            ] as $model
        ) {
            $model::observe(AuditLogObserver::class);
        }
    }
}
