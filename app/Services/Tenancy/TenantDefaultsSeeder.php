<?php

namespace App\Services\Tenancy;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\ApprovalThreshold;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\FiscalYear;
use App\Services\Admin\DefaultChartOfAccounts;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantDefaultsSeeder
{
    public function __construct(private readonly NumberingSequenceService $numberingSequences)
    {
    }

    public function seed(Company $company, string $connection, string $centralConnection): void
    {
        $centralBranches = DB::connection($centralConnection)
            ->table('branches')
            ->where('company_id', $company->id)
            ->get();

        DB::setDefaultConnection($connection);

        try {
            $this->seedBootstrapRows($company, $centralBranches);
            $this->seedFiscalYear($company);
            $this->createAccountingPeriods($company);
            $createdAccounts = $this->copyDefaultChartOfAccounts($company);
            $this->seedSupplementalAccounts($company);
            $this->seedDefaultAccountMappings($company, $createdAccounts);

            ApprovalSetting::create([
                'company_id' => $company->id,
                'requires_approval' => false,
                'threshold_amount' => 0,
            ]);

            foreach (ApprovalThreshold::documentTypes() as $type => $label) {
                ApprovalThreshold::create([
                    'company_id' => $company->id,
                    'document_type' => $type,
                    'threshold_amount' => 0,
                    'is_active' => false,
                ]);
            }

            $this->numberingSequences->seedDefaults($company->id);
        } finally {
            DB::setDefaultConnection($centralConnection);
        }
    }

    private function seedBootstrapRows(Company $company, $centralBranches): void
    {
        DB::table('companies')->updateOrInsert(
            ['id' => $company->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        if ($centralBranches->isEmpty()) {
            $code = strtoupper(Str::slug($company->company_code ?: 'HO', '_')) ?: 'HQ';

            DB::table('branches')->insert([
                'company_id' => $company->id,
                'name' => 'Head Office',
                'code' => $code,
                'address' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        foreach ($centralBranches as $branch) {
            DB::table('branches')->updateOrInsert(['id' => $branch->id], [
                'company_id' => $company->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'is_active' => (bool) $branch->is_active,
                'created_at' => $branch->created_at ?: now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedFiscalYear(Company $company): void
    {
        $startMonth = $company->fiscal_year_start_month;
        $year = (int) now()->format('Y');

        if (now()->month < $startMonth) {
            $year--;
        }

        $start = now()->create($year, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addYear()->subDay();
        $label = $start->format('Y') . '/' . $start->copy()->addYear()->format('Y');

        FiscalYear::firstOrCreate(
            ['company_id' => $company->id, 'label' => $label],
            ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'status' => 'open']
        );
    }

    private function createAccountingPeriods(Company $company): void
    {
        $startMonth = $company->fiscal_year_start_month;
        $year = (int) now()->format('Y');

        if (now()->month < $startMonth) {
            $year--;
        }

        for ($i = 0; $i < 12; $i++) {
            $month = (($startMonth - 1) + $i) % 12 + 1;
            $periodYear = $year + (int) (($startMonth - 1 + $i) / 12);

            $startDate = now()->create($periodYear, $month)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            AccountingPeriod::create([
                'company_id' => $company->id,
                'label' => $startDate->format('F Y'),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => $i === 0 ? 'open' : 'locked',
            ]);
        }
    }

    private function copyDefaultChartOfAccounts(Company $company): array
    {
        $createdAccounts = [];

        foreach (DefaultChartOfAccounts::get() as $accountData) {
            $existing = Account::where('company_id', $company->id)
                ->where('code', $accountData['code'])
                ->first();

            if ($existing) {
                $createdAccounts[$accountData['code']] = $existing->id;
                continue;
            }

            $parentCode = $accountData['parent_code'] ?? null;
            $parentId = null;

            if ($parentCode && isset($createdAccounts[$parentCode])) {
                $parentId = $createdAccounts[$parentCode];
            }

            $account = Account::create([
                'company_id' => $company->id,
                'parent_id' => $parentId,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'sub_type' => $accountData['sub_type'],
                'description' => $accountData['description'] ?? null,
                'opening_balance' => 0,
                'opening_balance_date' => null,
                'currency' => $company->base_currency,
                'is_bank_account' => ($accountData['code'] === '1000'),
                'is_active' => true,
            ]);

            $createdAccounts[$accountData['code']] = $account->id;
        }

        return $createdAccounts;
    }

    private function seedSupplementalAccounts(Company $company): void
    {
        $supplemental = [
            ['code' => '1050', 'name' => 'Undeposited Funds', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '2150', 'name' => 'Accrued Purchases', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '6800', 'name' => 'Purchase Price Variance', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '6850', 'name' => 'Inventory Count Variance', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '1700', 'name' => 'Accumulated Impairment Losses', 'type' => 'asset', 'sub_type' => 'non_current_asset'],
            ['code' => '7100', 'name' => 'Gain/Loss on Disposal of Fixed Assets', 'type' => 'expense', 'sub_type' => 'non_operating_expense'],
            ['code' => '3300', 'name' => 'Revaluation Surplus', 'type' => 'equity', 'sub_type' => 'equity'],
        ];

        foreach ($supplemental as $account) {
            $exists = Account::where('company_id', $company->id)
                ->where('code', $account['code'])
                ->exists();

            if (!$exists) {
                Account::create(array_merge($account, [
                    'company_id' => $company->id,
                    'is_active' => true,
                ]));
            }
        }
    }

    private function seedDefaultAccountMappings(Company $company, array $createdAccounts = []): void
    {
        $defaults = DefaultAccountMapping::defaultCodes();

        foreach ($defaults as $mappingKey => $accountCode) {
            if ($accountCode === null) {
                continue;
            }

            $accountId = $createdAccounts[$accountCode] ?? null;

            if (!$accountId) {
                $account = Account::where('company_id', $company->id)
                    ->where('code', $accountCode)
                    ->first();
                $accountId = $account?->id;
            }

            if ($accountId) {
                DefaultAccountMapping::create([
                    'company_id' => $company->id,
                    'mapping_key' => $mappingKey,
                    'account_id' => $accountId,
                ]);
            }
        }
    }
}
