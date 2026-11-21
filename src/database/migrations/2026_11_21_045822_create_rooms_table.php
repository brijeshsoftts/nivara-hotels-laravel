<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('name')->nullable();
            $table->text('room_type_id');
            $table->text('room_number')->unique();
            $table->integer('floor');
            $table->text('description')->nullable();
            $table->enum('occupancy_status', array_column(OccupancyStatus::cases(), 'value'));
            $table->enum('housekeeping_status', array_column(HousekeepingStatus::cases(), 'value'));
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('room_type_id')->references('id')->on('room_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
