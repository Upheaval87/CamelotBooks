<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $companies = DB::table('companies')->pluck('id');

        $accounts = [
            ['code' => '1700', 'name' => 'Accumulated Impairment Losses', 'type' => 'asset', 'sub_type' => 'non_current_asset'],
            ['code' => '6500', 'name' => 'Impairment Loss', 'type' => 'expense', 'sub_type' => 'non_operating_expense'],
            ['code' => '7100', 'name' => 'Gain/Loss on Disposal of Fixed Assets', 'type' => 'expense', 'sub_type' => 'non_operating_expense'],
            ['code' => '3300', 'name' => 'Revaluation Surplus', 'type' => 'equity', 'sub_type' => 'equity'],
        ];

        $now = now()->format('Y-m-d H:i:s');

        foreach ($companies as $companyId) {
            foreach ($accounts as $account) {
                $exists = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $account['code'])
                    ->exists();

                if (!$exists) {
                    DB::table('accounts')->insert([
                        'company_id' => $companyId,
                        'code' => $account['code'],
                        'name' => $account['name'],
                        'type' => $account['type'],
                        'sub_type' => $account['sub_type'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', ['1700', '6500', '7100', '3300'])->delete();
    }
};
