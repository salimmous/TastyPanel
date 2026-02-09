<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');

            // Action details
            $table->string('action'); // create, update, delete, login, logout, etc.
            $table->string('resource_type')->nullable(); // Recipe, User, Tenant, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('description')->nullable();

            // Changes tracking
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request details
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('url')->nullable();

            // Status
            $table->string('status')->default('success'); // success, failed
            $table->text('error_message')->nullable();

            $table->timestamp('created_at');

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
