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
        Schema::create('housekeeper_room_assignments', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('housekeeper_id');
            $table->text('room_id');
            $table->text('assigned_by');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('housekeeper_id')->references('id')->on('users');
            $table->foreign('room_id')->references('id')->on('rooms');
            $table->foreign('assigned_by')->references('id')->on('users');
            $table->unique(['housekeeper_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('housekeeper_room_assignments');
    }
};
