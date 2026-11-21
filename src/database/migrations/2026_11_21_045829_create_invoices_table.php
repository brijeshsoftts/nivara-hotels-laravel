<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\InvoiceStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('invoice_number')->unique();
            $table->text('booking_id')->unique();
            $table->text('customer_id')->nullable();
            $table->integer('subtotal');
            $table->integer('tax_amount');
            $table->integer('discount_amount');
            $table->integer('total_amount');
            $table->enum('status', array_column(InvoiceStatus::cases(), 'value'))->default(InvoiceStatus::UNPAID->value);
            $table->timestamp('issued_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('customer_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
