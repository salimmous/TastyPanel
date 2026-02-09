<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_traffic_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('requests')->default(0);
            $table->unsignedBigInteger('unique_ips')->default(0);
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedBigInteger('status_4xx')->default(0);
            $table->unsignedBigInteger('status_5xx')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_traffic_metrics');
    }
};
