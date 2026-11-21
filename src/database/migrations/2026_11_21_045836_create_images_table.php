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
        Schema::create('images', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('url');
            $table->text('alt_text')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->text('room_type_id')->nullable();

            $table->index('url');
            $table->index('room_type_id');
            $table->foreign('room_type_id')->references('id')->on('room_types')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('profile_id')->references('id')->on('images');
        });
        Schema::table('staff', function (Blueprint $table) {
            $table->foreign('id_proof_image_id')->references('id')->on('images');
            $table->foreign('signature_image_id')->references('id')->on('images');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('id_proof_image_id')->references('id')->on('images');
            $table->foreign('signature_image_id')->references('id')->on('images');
        });
        Schema::table('issues', function (Blueprint $table) {
            $table->foreign('image_id')->references('id')->on('images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['id_proof_image_id']);
            $table->dropForeign(['signature_image_id']);
        });
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['id_proof_image_id']);
            $table->dropForeign(['signature_image_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['profile_id']);
        });
        Schema::dropIfExists('images');
    }
};
