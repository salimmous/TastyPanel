<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->json('tags')->nullable()->after('category');
            $table->string('author')->nullable()->after('tags');
            $table->string('version')->nullable()->after('author');
            $table->string('preview_image')->nullable()->after('version');
            $table->boolean('is_featured')->default(false)->after('preview_image');
            $table->boolean('is_marketplace')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'tags',
                'author',
                'version',
                'preview_image',
                'is_featured',
                'is_marketplace',
            ]);
        });
    }
};
