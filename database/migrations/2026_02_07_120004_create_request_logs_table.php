<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();

            // Request info
            $table->string('method', 10);
            $table->string('path', 500);
            $table->string('full_url', 1000)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // User info
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('set null');

            // Request details
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('body')->nullable();

            // Response
            $table->integer('status_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('response_size')->nullable();

            // Error info
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes for querying
            $table->index('created_at');
            $table->index(['method', 'status_code']);
            $table->index('user_id');
            $table->index('tenant_id');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
