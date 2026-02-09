<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ip_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');

            // IP details
            $table->string('ip_address'); // Supports CIDR: 192.168.1.0/24
            $table->enum('type', ['whitelist', 'blacklist']);

            // Metadata
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            // Auto-ban details
            $table->boolean('is_auto_ban')->default(false);
            $table->integer('failed_attempts')->default(0);

            // Expiration
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_permanent')->default(false);

            // Who created it
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'type']);
            $table->index('ip_address');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_restrictions');
    }
};
