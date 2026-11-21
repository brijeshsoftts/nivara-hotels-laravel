<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('payment_reference')->unique();
            $table->text('transaction_id')->nullable()->unique();
            $table->text('invoice_id');
            $table->text('booking_id');
            $table->text('customer_id')->nullable();
            $table->integer('amount');
            $table->integer('amount_paise')->nullable();
            $table->text('currency')->default('INR');
            $table->enum('payment_method', array_column(PaymentMethod::cases(), 'value'));
            $table->enum('payment_status', array_column(PaymentStatus::cases(), 'value'))->default(PaymentStatus::PENDING->value);
            $table->text('order_id')->nullable()->unique();
            $table->timestamp('order_creation_started_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('recorded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('customer_id')->references('id')->on('users');
            $table->foreign('recorded_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
