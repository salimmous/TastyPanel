<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_publications', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation
            $table->string('schedulable_type'); // Recipe, Article, etc.
            $table->unsignedBigInteger('schedulable_id');

            // Scheduling details
            $table->string('action'); // publish, unpublish, update
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();

            // Status: pending, executing, completed, failed
            $table->string('status')->default('pending');

            // Who scheduled
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Optional data (for update action)
            $table->json('data')->nullable();

            // Error tracking
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['schedulable_type', 'schedulable_id']);
            $table->index(['status', 'scheduled_at']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_publications');
    }
};
