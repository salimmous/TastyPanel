<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('instance_ssh_user', 64)->nullable()->after('instance_db_password');
            $table->string('instance_ssh_home')->nullable()->after('instance_ssh_user');
            $table->unsignedSmallInteger('instance_ssh_port')->nullable()->after('instance_ssh_home');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'instance_ssh_user',
                'instance_ssh_home',
                'instance_ssh_port',
            ]);
        });
    }
};

