<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Branding
            $table->string('brand_name')->nullable()->after('analytics_config');
            $table->string('brand_logo')->nullable()->after('brand_name');
            $table->string('brand_favicon')->nullable()->after('brand_logo');
            $table->string('brand_color')->default('#3b82f6')->after('brand_favicon');
            $table->string('brand_secondary_color')->default('#8b5cf6')->after('brand_color');

            // White label
            $table->boolean('white_label_enabled')->default(false)->after('brand_secondary_color');
            $table->text('custom_css')->nullable()->after('white_label_enabled');
            $table->text('custom_js')->nullable()->after('custom_css');

            // Footer
            $table->boolean('hide_powered_by')->default(false)->after('custom_js');
            $table->text('custom_footer')->nullable()->after('hide_powered_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'brand_name',
                'brand_logo',
                'brand_favicon',
                'brand_color',
                'brand_secondary_color',
                'white_label_enabled',
                'custom_css',
                'custom_js',
                'hide_powered_by',
                'custom_footer',
            ]);
        });
    }
};
