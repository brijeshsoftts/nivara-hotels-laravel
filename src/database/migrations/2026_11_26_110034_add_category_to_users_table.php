<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('category', array_column(Category::cases(), 'value'))
                ->nullable()
                ->after('role');
        });

        DB::table('staff')->get(['user_id', 'category'])->each(function (object $staff): void {
            DB::table('users')
                ->where('id', $staff->user_id)
                ->update(['category' => $staff->category]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
