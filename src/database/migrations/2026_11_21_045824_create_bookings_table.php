<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('booking_reference')->unique();
            $table->text('customer_id')->nullable();
            $table->text('guest_name')->nullable();
            $table->text('guest_email')->nullable();
            $table->text('guest_phone')->nullable();
            $table->text('public_checkout_token_hash')->nullable()->unique();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->timestamp('check_in_date');
            $table->timestamp('check_out_date');
            $table->integer('total_guests');
            $table->integer('total_amount');
            $table->enum('payment_method', array_column(PaymentMethod::cases(), 'value'))->nullable();
            $table->text('special_request')->nullable();
            $table->enum('status', array_column(BookingStatus::cases(), 'value'))->default(BookingStatus::PENDING->value);
            $table->timestamp('booked_at');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('created_by')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
