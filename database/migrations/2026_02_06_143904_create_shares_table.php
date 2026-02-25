<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');

            // Platform: facebook, twitter, whatsapp, email, copy_link, pinterest
            $table->string('platform', 50);

            // IP and context for anonymous shares
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at');

            // Indexes
            $table->index('recipe_id');
            $table->index(['recipe_id', 'platform']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
