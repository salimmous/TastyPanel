<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_indices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Provider configuration
            $table->string('provider')->default('database'); // meilisearch, algolia, typesense, database
            $table->string('index_name');
            $table->string('status')->default('active'); // active, indexing, error

            // Statistics
            $table->integer('documents_count')->default(0);
            $table->timestamp('last_indexed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            // Configuration & settings
            $table->json('settings')->nullable(); // provider-specific settings
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'provider']);
            $table->unique(['tenant_id', 'index_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_indices');
    }
};
