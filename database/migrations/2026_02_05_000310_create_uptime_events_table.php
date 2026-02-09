<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uptime_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uptime_check_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('status')->nullable();
            $table->integer('response_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_events');
    }
};
