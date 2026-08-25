<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip when the tenant connection is unavailable (e.g. central DB migrate)
        if (!config('database.connections.tenant') && !config('tenancy.routing.connection_override')) {
            return;
        }

        try {
            $conn = DB::connection(config('tenancy.routing.connection_override') ?: 'tenant');
        } catch (\Throwable) {
            return;
        }

        // Each tenant DB has exactly one company
        $companyId = $conn->table('companies')->value('id');
        if (!$companyId) {
            return;
        }

        // Seed 3 GL accounts for the returnables/bottle-credit workflow
        $accounts = [
            [
                'code' => '1320',
                'name' => 'Returnable Containers',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Deposit value of bottles and returnable containers held on behalf of customers',
            ],
            [
                'code' => '2350',
                'name' => 'Customer Bottle Credits Liability',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Refundable deposits owed to customers for returned bottles and containers',
            ],
            [
                'code' => '4050',
                'name' => 'Bottle Deposit Revenue',
                'type' => 'income',
                'sub_type' => 'revenue',
                'description' => 'Revenue from forfeited bottle deposits when return window expires',
            ],
        ];

        $now = now()->toDateTimeString();

        foreach ($accounts as $account) {
            $conn->table('accounts')->updateOrInsert(
                ['code' => $account['code']],
                array_merge($account, [
                    'company_id' => $companyId,
                    'opening_balance' => 0,
                    'opening_balance_date' => null,
                    'currency' => 'MWK',
                    'is_bank_account' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // Seed default account mappings
        $mappingKeys = [
            'returnable_containers' => '1320',
            'bottle_credits_liability' => '2350',
            'bottle_deposit_revenue' => '4050',
        ];

        foreach ($mappingKeys as $key => $code) {
            $accountId = $conn->table('accounts')->where('code', $code)->value('id');
            if ($accountId) {
                $conn->table('default_account_mappings')->updateOrInsert(
                    ['company_id' => $companyId, 'mapping_key' => $key],
                    ['account_id' => $accountId, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        // Seed BRR numbering sequence
        $conn->table('numbering_sequences')->updateOrInsert(
            ['company_id' => $companyId, 'document_type' => 'bottle_return_receipt'],
            [
                'prefix' => 'BRR-',
                'padding_width' => 5,
                'next_number' => 1,
                'reset_policy' => 'annually',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        try {
            $conn = DB::connection(config('tenancy.routing.connection_override') ?: 'tenant');
        } catch (\Throwable) {
            return;
        }

        $conn->table('numbering_sequences')
            ->where('document_type', 'bottle_return_receipt')->delete();

        $mappingKeys = ['returnable_containers', 'bottle_credits_liability', 'bottle_deposit_revenue'];
        $conn->table('default_account_mappings')
            ->whereIn('mapping_key', $mappingKeys)->delete();

        $conn->table('accounts')
            ->whereIn('code', ['1320', '2350', '4050'])->delete();
    }
};
