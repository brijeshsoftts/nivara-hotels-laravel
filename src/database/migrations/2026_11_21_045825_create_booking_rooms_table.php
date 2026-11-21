<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('booking_id')->unique();
            $table->text('room_type_id');
            $table->text('assigned_room_id')->nullable();
            $table->integer('price_per_night');

            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('room_type_id')->references('id')->on('room_types');
            $table->foreign('assigned_room_id')->references('id')->on('rooms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_rooms');
    }
};
