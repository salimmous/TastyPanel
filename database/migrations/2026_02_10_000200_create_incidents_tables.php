<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();

            // Unique within a tenant, used to deduplicate issues across runs.
            $table->string('fingerprint', 190);

            $table->string('category', 50); // uptime|ssl|backup|http3|storage|...
            $table->string('status', 20)->default('open'); // open|acked|snoozed|resolved
            $table->string('severity', 20)->default('medium'); // low|medium|high|critical

            $table->string('title');
            $table->text('summary')->nullable();

            $table->string('resource_type', 50)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();

            $table->json('meta')->nullable();

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->foreignId('acked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acked_at')->nullable();

            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'fingerprint']);
            $table->index(['status', 'category']);
            $table->index(['tenant_id', 'status']);
            $table->index('last_seen_at');
        });

        Schema::create('incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('kind', 30); // created|reopened|acked|unacked|snoozed|unsnoozed|resolved|auto_resolved|note
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_events');
        Schema::dropIfExists('incidents');
    }
};
