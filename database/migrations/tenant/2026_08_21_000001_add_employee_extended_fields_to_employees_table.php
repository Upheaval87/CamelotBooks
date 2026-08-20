<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nationality', 100)->nullable()->after('address');
            $table->string('marital_status', 20)->nullable()->after('nationality');
            $table->unsignedSmallInteger('dependents')->nullable()->after('marital_status');
            $table->string('place_of_residence', 255)->nullable()->after('dependents');
            $table->string('home_village', 255)->nullable()->after('place_of_residence');
            $table->string('home_district', 100)->nullable()->after('home_village');
            $table->string('nok_name', 255)->nullable()->after('home_district');
            $table->string('nok_relationship', 50)->nullable()->after('nok_name');
            $table->string('nok_phone', 50)->nullable()->after('nok_relationship');
            $table->date('employment_end_date')->nullable()->after('termination_date');
            $table->string('employment_type', 30)->nullable()->after('employment_status');
            $table->string('payment_method', 30)->nullable()->after('bank_branch_code');
            $table->string('mobile_money_provider', 100)->nullable()->after('payment_method');
            $table->string('mobile_money_number', 50)->nullable()->after('mobile_money_provider');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'nationality', 'marital_status', 'dependents',
                'place_of_residence', 'home_village', 'home_district',
                'nok_name', 'nok_relationship', 'nok_phone',
                'employment_end_date', 'employment_type',
                'payment_method', 'mobile_money_provider', 'mobile_money_number',
            ]);
        });
    }
};
