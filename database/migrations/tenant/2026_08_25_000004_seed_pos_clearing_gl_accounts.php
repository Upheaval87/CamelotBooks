<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!config('tenancy.routing.connection_override')) {
            return;
        }

        try {
            $companyId = DB::connection('tenant')->table('companies')->value('id');
            if (!$companyId) {
                return;
            }

            $accounts = [
                ['code' => '1060', 'name' => 'Cash in Drawer', 'type' => 'asset', 'sub_type' => 'current_asset'],
                ['code' => '1070', 'name' => 'Card Clearing Account', 'type' => 'asset', 'sub_type' => 'current_asset'],
                ['code' => '1080', 'name' => 'Mobile Money Clearing', 'type' => 'asset', 'sub_type' => 'current_asset'],
                ['code' => '6900', 'name' => 'Cash Shortage', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                ['code' => '7400', 'name' => 'Cash Overage', 'type' => 'income', 'sub_type' => 'other_income'],
                ['code' => '6950', 'name' => 'Merchant Fee Expense', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ];

            foreach ($accounts as $account) {
                $exists = DB::connection('tenant')->table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $account['code'])
                    ->exists();

                if (!$exists) {
                    DB::connection('tenant')->table('accounts')->insert(array_merge($account, [
                        'company_id' => $companyId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        } catch (\Throwable $e) {
            // Silently skip — accounts may already exist or table may not be ready
        }
    }

    public function down(): void
    {
        if (!config('tenancy.routing.connection_override')) {
            return;
        }

        try {
            $companyId = DB::connection('tenant')->table('companies')->value('id');
            if ($companyId) {
                DB::connection('tenant')->table('accounts')
                    ->where('company_id', $companyId)
                    ->whereIn('code', ['1060', '1070', '1080', '6900', '7400', '6950'])
                    ->delete();
            }
        } catch (\Throwable $e) {
            // Silently skip
        }
    }
};
