<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_queue_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('high_queue')->default('high');
            $table->string('default_queue')->default('default');
            $table->string('low_queue')->default('low');
            $table->unsignedInteger('min_workers')->default(1);
            $table->unsignedInteger('max_workers')->default(4);
            $table->unsignedInteger('scale_up_threshold')->default(100);
            $table->unsignedInteger('scale_down_threshold')->default(20);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_queue_profiles');
    }
};
