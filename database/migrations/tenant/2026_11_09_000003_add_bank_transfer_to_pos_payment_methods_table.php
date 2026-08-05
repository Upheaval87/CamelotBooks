<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN; enum is not enforced there anyway
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('pos_payment_methods', function (Blueprint $table) {
                $table->enum('type', ['cash', 'card', 'mobile_money', 'bank_transfer'])->default('cash')->change();
            });
        }

        // Seed Bank Transfer payment method for each company
        $companies = DB::table('companies')->pluck('id');
        foreach ($companies as $companyId) {
            $clearingAccount = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '1010')
                ->first();

            DB::table('pos_payment_methods')->updateOrInsert(
                ['company_id' => $companyId, 'name' => 'Bank Transfer'],
                [
                    'type' => 'bank_transfer',
                    'clearing_account_id' => $clearingAccount?->id,
                    'requires_reference' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Remove Bank Transfer payment methods
        DB::table('pos_payment_methods')->where('name', 'Bank Transfer')->delete();

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('pos_payment_methods', function (Blueprint $table) {
                $table->enum('type', ['cash', 'card', 'mobile_money'])->default('cash')->change();
            });
        }
    }
};
