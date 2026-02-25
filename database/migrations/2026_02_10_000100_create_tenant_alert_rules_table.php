<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->boolean('enabled')->default(true);

            $table->text('emails')->nullable();
            $table->text('slack_webhook')->nullable();

            $table->unsignedInteger('interval_hours')->nullable();
            $table->unsignedInteger('ssl_days')->nullable();

            $table->boolean('notify_ssl')->default(true);
            $table->boolean('notify_uptime')->default(true);
            $table->boolean('notify_backup')->default(true);
            $table->boolean('notify_http3')->default(false);
            $table->boolean('notify_storage')->default(true);

            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_alert_rules');
    }
};
