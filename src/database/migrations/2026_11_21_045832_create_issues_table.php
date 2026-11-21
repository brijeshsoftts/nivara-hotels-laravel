<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('reference')->unique();
            $table->text('room_id');
            $table->text('reported_by_id');
            $table->text('assigned_to_id')->nullable();
            $table->enum('category', array_column(IssueCategory::cases(), 'value'));
            $table->enum('priority', array_column(IssuePriority::cases(), 'value'));
            $table->enum('status', array_column(IssueStatus::cases(), 'value'))->default(IssueStatus::OPEN->value);
            $table->text('title');
            $table->text('description');
            $table->text('image_id')->nullable();
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('rooms');
            $table->foreign('reported_by_id')->references('id')->on('users');
            $table->foreign('assigned_to_id')->references('id')->on('users');
            $table->index('room_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to_id');
            $table->index('reported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
