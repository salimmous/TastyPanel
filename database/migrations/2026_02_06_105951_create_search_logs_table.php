<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Search details
            $table->string('query');
            $table->string('type')->nullable(); // recipe, article, all
            $table->integer('results_count')->default(0);
            $table->float('response_time')->nullable(); // in milliseconds

            // Filters applied
            $table->json('filters')->nullable();

            // User tracking
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Session & referrer
            $table->string('session_id')->nullable();
            $table->string('referrer')->nullable();

            $table->timestamp('created_at');

            // Indexes for analytics
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'query']); // for popular searches
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
