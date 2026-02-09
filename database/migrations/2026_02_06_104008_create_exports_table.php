<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Export details
            $table->string('format'); // csv, json, wordpress, pdf, excel
            $table->string('type')->default('recipe'); // recipe, article, category, all
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('file_path')->nullable();
            $table->string('filename')->nullable();
            $table->integer('file_size')->nullable(); // in bytes

            // Filters and options
            $table->json('filters')->nullable(); // category, date range, status, etc.
            $table->json('options')->nullable(); // include images, format options

            // Progress tracking
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);

            // Error tracking
            $table->text('error_message')->nullable();

            // File management
            $table->timestamp('expires_at')->nullable(); // auto-delete after 7 days
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
