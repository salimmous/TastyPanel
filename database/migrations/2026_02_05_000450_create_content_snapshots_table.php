<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('environment')->default('production');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_categories')->default(0);
            $table->unsignedInteger('total_recipes')->default(0);
            $table->unsignedInteger('total_articles')->default(0);
            $table->json('data');
            $table->timestamps();

            $table->index(['tenant_id', 'environment', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_snapshots');
    }
};
