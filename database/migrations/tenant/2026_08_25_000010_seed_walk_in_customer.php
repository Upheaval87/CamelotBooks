<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            $exists = DB::table('customers')
                ->where('company_id', $companyId)
                ->where('name', 'Walk-in Customer')
                ->exists();

            if (!$exists) {
                DB::table('customers')->insert([
                    'company_id' => $companyId,
                    'name' => 'Walk-in Customer',
                    'display_name' => 'Walk-in Customer',
                    'is_active' => true,
                    'payment_terms' => 'cash',
                    'payment_terms_days' => 0,
                    'credit_limit' => 0,
                    'opening_balance' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('name', 'Walk-in Customer')
            ->delete();
    }
};
