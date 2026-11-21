<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\BedType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('name');
            $table->text('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('size_sq_ft')->nullable();
            $table->integer('max_guests');
            $table->integer('adults')->default(2);
            $table->integer('children')->default(0);
            $table->integer('base_price');
            $table->text('currency')->default('INR');
            $table->enum('bed_type', array_column(BedType::cases(), 'value'));
            $table->integer('bed_count')->default(1);
            $table->boolean('smoking_allowed')->default(false);
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index('base_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
