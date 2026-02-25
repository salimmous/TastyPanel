<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Category: system, content, security, webhook, job, performance
            $table->string('category');

            // Notification channels
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);

            $table->timestamps();

            // Unique constraint - one setting per user per category
            $table->unique(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
