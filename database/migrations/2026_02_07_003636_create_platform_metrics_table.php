<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');

            // Tenant metrics
            $table->integer('total_tenants')->default(0);
            $table->integer('active_tenants')->default(0);
            $table->integer('new_tenants')->default(0);
            $table->integer('churned_tenants')->default(0);

            // Content metrics
            $table->integer('total_recipes')->default(0);
            $table->integer('total_articles')->default(0);
            $table->integer('new_recipes')->default(0);
            $table->integer('new_articles')->default(0);

            // Traffic metrics
            $table->bigInteger('total_requests')->default(0);
            $table->bigInteger('total_bytes')->default(0);
            $table->integer('unique_visitors')->default(0);

            // Performance metrics
            $table->decimal('avg_response_time', 8, 2)->nullable();
            $table->integer('error_count')->default(0);
            $table->decimal('cache_hit_rate', 5, 2)->nullable();

            // Storage metrics
            $table->bigInteger('total_storage_bytes')->default(0);

            $table->timestamps();

            $table->unique('date');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_metrics');
    }
};
