<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');

            // Request details
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('status_code');

            // Performance metrics
            $table->float('response_time'); // in milliseconds
            $table->integer('memory_usage'); // in bytes
            $table->integer('query_count')->default(0);
            $table->float('query_time')->default(0); // in milliseconds

            // Additional metrics
            $table->integer('cache_hits')->default(0);
            $table->integer('cache_misses')->default(0);
            $table->boolean('is_slow')->default(false); // > 1000ms

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamp('created_at');

            // Indexes
            $table->index(['endpoint', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['is_slow', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
