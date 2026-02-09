<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_mail_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_mailbox_id')->nullable()->constrained('tenant_mailboxes')->nullOnDelete();
            $table->string('direction', 16)->default('outbound');
            $table->string('event_type', 64);
            $table->string('recipient', 190)->nullable();
            $table->string('status', 32)->default('success');
            $table->string('message_id', 190)->nullable();
            $table->text('response')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_mail_events');
    }
};
