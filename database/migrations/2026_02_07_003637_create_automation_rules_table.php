<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Rule type: maintenance, cleanup, alert, action
            $table->string('type');

            // Trigger: schedule, event, condition
            $table->string('trigger_type');
            $table->json('trigger_config')->nullable();

            // Conditions (JSON array)
            $table->json('conditions')->nullable();

            // Actions to perform
            $table->json('actions');

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(50);

            // Execution tracking
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->text('last_run_output')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'trigger_type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
