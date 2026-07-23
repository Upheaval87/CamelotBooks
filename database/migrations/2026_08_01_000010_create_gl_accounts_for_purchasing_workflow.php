<?php

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['code' => '1050', 'name' => 'Undeposited Funds', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '2150', 'name' => 'Accrued Purchases', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '6800', 'name' => 'Purchase Price Variance', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '6850', 'name' => 'Inventory Count Variance', 'type' => 'expense', 'sub_type' => 'operating_expense'],
        ];

        foreach (Company::all() as $company) {
            foreach ($accounts as $acct) {
                $exists = Account::where('company_id', $company->id)
                    ->where('code', $acct['code'])
                    ->exists();
                if (!$exists) {
                    Account::create(array_merge($acct, [
                        'company_id' => $company->id,
                        'is_active' => true,
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        Account::whereIn('code', ['1050', '2150', '6800', '6850'])->delete();
    }
};
