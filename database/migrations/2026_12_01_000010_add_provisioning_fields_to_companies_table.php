<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('provisioning_status')->default('pending')->after('is_active');
            $table->string('db_name')->nullable()->unique()->after('provisioning_status');
            $table->string('db_host')->nullable()->after('db_name');
            $table->unsignedSmallInteger('db_port')->nullable()->after('db_host');
            $table->string('db_username')->nullable()->after('db_port');
            $table->text('db_password')->nullable()->after('db_username');
            $table->timestamp('provisioned_at')->nullable()->after('db_password');
            $table->text('last_provisioning_error')->nullable()->after('provisioned_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'provisioning_status',
                'db_name',
                'db_host',
                'db_port',
                'db_username',
                'db_password',
                'provisioned_at',
                'last_provisioning_error',
            ]);
        });
    }
};
