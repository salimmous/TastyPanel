<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // TOTP secret (encrypted)
            $table->text('secret');

            // Recovery codes (encrypted JSON array)
            $table->text('recovery_codes');

            // Status
            $table->boolean('enabled')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('enabled_at')->nullable();

            // Remember device tokens
            $table->json('trusted_devices')->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_secrets');
    }
};
