<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Import details
            $table->string('format'); // csv, json, wordpress, excel
            $table->string('type')->default('recipe'); // recipe, article, category
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->integer('file_size')->nullable(); // in bytes

            // Progress tracking
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('skipped_count')->default(0);

            // Configuration
            $table->json('mapping')->nullable(); // field mapping configuration
            $table->json('options')->nullable(); // import options (e.g., update existing, skip duplicates)

            // Error tracking
            $table->json('errors')->nullable(); // array of error messages
            $table->text('error_file_path')->nullable(); // path to detailed error log

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
