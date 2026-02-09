<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('instance_key', 80)->nullable()->after('status');
            $table->string('instance_root')->nullable()->after('instance_key');
            $table->string('instance_public_root')->nullable()->after('instance_root');
            $table->string('instance_php_socket')->nullable()->after('instance_public_root');
            $table->string('instance_db_name', 64)->nullable()->after('instance_php_socket');
            $table->string('instance_db_user', 64)->nullable()->after('instance_db_name');
            $table->string('instance_db_password')->nullable()->after('instance_db_user');
            $table->string('instance_status', 40)->nullable()->after('instance_db_password');
            $table->text('instance_last_error')->nullable()->after('instance_status');
            $table->timestamp('instance_installed_at')->nullable()->after('instance_last_error');

            $table->index('instance_key');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['instance_key']);
            $table->dropColumn([
                'instance_key',
                'instance_root',
                'instance_public_root',
                'instance_php_socket',
                'instance_db_name',
                'instance_db_user',
                'instance_db_password',
                'instance_status',
                'instance_last_error',
                'instance_installed_at',
            ]);
        });
    }
};
