<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('image');
            $table->string('video_embed')->nullable()->after('video_url');
            $table->string('video_provider')->nullable()->after('video_embed'); // youtube, vimeo, tiktok
            $table->integer('video_duration')->nullable()->after('video_provider'); // seconds
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'video_embed', 'video_provider', 'video_duration']);
        });
    }
};
