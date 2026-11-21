<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('user_id')->unique();
            $table->text('father_name');
            $table->text('mother_name');
            $table->text('id_proof_image_id')->nullable();
            $table->text('id_proof_number');
            $table->text('qualification');
            $table->text('experience');
            $table->enum('category', array_column(Category::cases(), 'value'));
            $table->text('emergency_contact');
            $table->text('address');
            $table->text('signature_image_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
