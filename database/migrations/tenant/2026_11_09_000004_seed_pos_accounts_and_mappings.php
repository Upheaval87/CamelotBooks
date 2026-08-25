<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure the type column accepts all POS payment method types
        if (DB::getDriverName() === 'sqlite' && Schema::hasTable('pos_payment_methods')) {
            // SQLite stores ENUM as a CHECK constraint on CREATE TABLE.
            // Recreate the table with all 5 enum values (cash/card/mobile_money/bank_transfer/customer_credit).
            $hasFeePercent = Schema::hasColumn('pos_payment_methods', 'fee_percent');

            if ($hasFeePercent) {
                DB::statement("CREATE TABLE pos_payment_methods_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'cash',
                    clearing_account_id INTEGER,
                    settlement_bank_account_id INTEGER,
                    requires_reference INTEGER NOT NULL DEFAULT 0,
                    is_active INTEGER NOT NULL DEFAULT 1,
                    fee_percent NUMERIC(5,2) DEFAULT 0,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                    FOREIGN KEY (clearing_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
                    FOREIGN KEY (settlement_bank_account_id) REFERENCES accounts(id) ON DELETE SET NULL
                )");
                DB::statement("INSERT INTO pos_payment_methods_new (id, company_id, name, type, clearing_account_id, settlement_bank_account_id, requires_reference, is_active, fee_percent, created_at, updated_at) SELECT id, company_id, name, type, clearing_account_id, settlement_bank_account_id, requires_reference, is_active, COALESCE(fee_percent, 0), created_at, updated_at FROM pos_payment_methods");
            } else {
                DB::statement("CREATE TABLE pos_payment_methods_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'cash',
                    clearing_account_id INTEGER,
                    settlement_bank_account_id INTEGER,
                    requires_reference INTEGER NOT NULL DEFAULT 0,
                    is_active INTEGER NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                    FOREIGN KEY (clearing_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
                    FOREIGN KEY (settlement_bank_account_id) REFERENCES accounts(id) ON DELETE SET NULL
                )");
                DB::statement("INSERT INTO pos_payment_methods_new (id, company_id, name, type, clearing_account_id, settlement_bank_account_id, requires_reference, is_active, created_at, updated_at) SELECT id, company_id, name, type, clearing_account_id, settlement_bank_account_id, requires_reference, is_active, created_at, updated_at FROM pos_payment_methods");
            }

            DB::statement('DROP TABLE pos_payment_methods');
            DB::statement('ALTER TABLE pos_payment_methods_new RENAME TO pos_payment_methods');
            DB::statement('CREATE UNIQUE INDEX pos_payment_methods_company_name_unique ON pos_payment_methods (company_id, name)');
            DB::statement('CREATE INDEX pos_payment_methods_company_type_index ON pos_payment_methods (company_id, type)');
        } elseif (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pos_payment_methods MODIFY COLUMN type ENUM('cash','card','mobile_money','bank_transfer','customer_credit') NOT NULL DEFAULT 'cash'");
        }

        // 2. This migration runs once per tenant DB (via tenant:migrate).
        //    The tenant companies table has a single row with our company_id.
        $companyRow = DB::table('companies')->first();
        if (!$companyRow) {
            return;
        }
        $companyId = $companyRow->id;

        $this->ensurePosAccountsAndMappings($companyId);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pos_payment_methods MODIFY COLUMN type ENUM('cash','card','mobile_money','bank_transfer') NOT NULL DEFAULT 'cash'");
        }
        // SQLite table was recreated — no way to remove the column from CHECK
        // but the table structure is equivalent minus customer_credit data
    }

    private function ensurePosAccountsAndMappings(int $companyId): void
    {
        // POS accounts that may not exist yet (same as PosSetupService pattern)
        $posAccounts = [
            '1050' => ['name' => 'Undeposited Funds', 'type' => 'asset', 'sub_type' => 'current_asset'],
            '1060' => ['name' => 'Cash-in-Drawer', 'type' => 'asset', 'sub_type' => 'current_asset'],
            '1070' => ['name' => 'Card Clearing', 'type' => 'asset', 'sub_type' => 'current_asset'],
            '1080' => ['name' => 'Mobile Money Clearing', 'type' => 'asset', 'sub_type' => 'current_asset'],
            '6900' => ['name' => 'Cash Shortage', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            '6950' => ['name' => 'Merchant Processing Fees', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            '7400' => ['name' => 'Cash Overage', 'type' => 'income', 'sub_type' => 'other_income'],
        ];

        foreach ($posAccounts as $code => $attrs) {
            DB::table('accounts')->updateOrInsert(
                ['company_id' => $companyId, 'code' => $code],
                array_merge($attrs, ['company_id' => $companyId, 'code' => $code, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Resolve account IDs for mappings
        $accountIds = DB::table('accounts')
            ->where('company_id', $companyId)
            ->whereIn('code', ['1050', '1060', '6900', '6950', '7400', '1100', '1200', '4000', '5000'])
            ->pluck('id', 'code');

        // POS-critical default account mappings
        $mappings = [
            'cash_in_drawer'      => '1060',
            'cash_shortage'       => '6900',
            'cash_overage'        => '7400',
            'merchant_fee_expense' => '6950',
            'undeposited_funds'   => '1050',
            'accounts_receivable' => '1100',
            'inventory_asset'     => '1200',
            'default_revenue'     => '4000',
            'default_expense'     => '5000',
        ];

        foreach ($mappings as $key => $code) {
            if (isset($accountIds[$code])) {
                DB::table('default_account_mappings')->updateOrInsert(
                    ['company_id' => $companyId, 'mapping_key' => $key],
                    ['company_id' => $companyId, 'mapping_key' => $key, 'account_id' => $accountIds[$code], 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Seed Customer Credit payment method if missing
        $arAccountId = $accountIds['1100'] ?? null;
        if ($arAccountId) {
            DB::table('pos_payment_methods')->updateOrInsert(
                ['company_id' => $companyId, 'name' => 'Customer Credit'],
                [
                    'company_id'            => $companyId,
                    'name'                  => 'Customer Credit',
                    'type'                  => 'customer_credit',
                    'clearing_account_id'   => $arAccountId,
                    'requires_reference'    => false,
                    'is_active'             => true,
                    'fee_percent'           => 0,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]
            );
        }
    }
};
