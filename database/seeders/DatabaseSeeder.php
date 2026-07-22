<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'current_company_id' => null,
        ]);

        $accountant = User::create([
            'name' => 'Accountant User',
            'email' => 'accountant@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'current_company_id' => null,
        ]);

        $companyA = Company::create([
            'name' => 'Acme Corporation',
            'legal_name' => 'Acme Corporation Ltd.',
            'company_code' => 'ACME',
            'tax_id' => '123456789',
            'address' => '123 Business Ave',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'US',
            'postal_code' => '10001',
            'phone' => '+1-555-0100',
            'email' => 'info@acme.test',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $companyB = Company::create([
            'name' => 'Beta Industries',
            'legal_name' => 'Beta Industries Inc.',
            'company_code' => 'BETA',
            'tax_id' => '987654321',
            'address' => '456 Corporate Blvd',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'country' => 'US',
            'postal_code' => '90001',
            'phone' => '+1-555-0200',
            'email' => 'info@beta.test',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $branchA1 = $companyA->branches()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'address' => '123 Business Ave',
            'is_active' => true,
        ]);

        $branchA2 = $companyA->branches()->create([
            'name' => 'West Branch',
            'code' => 'WB',
            'address' => '789 West St',
            'is_active' => true,
        ]);

        $branchB1 = $companyB->branches()->create([
            'name' => 'Main Office',
            'code' => 'MO',
            'address' => '456 Corporate Blvd',
            'is_active' => true,
        ]);

        $branchB2 = $companyB->branches()->create([
            'name' => 'East Branch',
            'code' => 'EB',
            'address' => '321 East Rd',
            'is_active' => true,
        ]);

        $admin->companies()->attach($companyA->id, ['role' => 'company_admin']);
        $admin->companies()->attach($companyB->id, ['role' => 'company_admin']);
        $accountant->companies()->attach($companyA->id, ['role' => 'accountant']);
        $accountant->companies()->attach($companyB->id, ['role' => 'accountant']);

        $admin->update(['current_company_id' => $companyA->id]);

        $this->createAccountingPeriods($companyA);
        $this->createAccountingPeriods($companyB);

        $this->copyDefaultChartOfAccounts($companyA);
        $this->copyDefaultChartOfAccounts($companyB);

        ApprovalSetting::create([
            'company_id' => $companyA->id,
            'requires_approval' => false,
            'threshold_amount' => 0,
        ]);

        ApprovalSetting::create([
            'company_id' => $companyB->id,
            'requires_approval' => false,
            'threshold_amount' => 0,
        ]);

        $this->createSampleJournalEntries($companyA, $branchA1, $admin, $accountant);
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

    private function copyDefaultChartOfAccounts(Company $company): void
    {
        $accounts = [
            [
                'code' => '1000',
                'name' => 'Cash and Cash Equivalents',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Cash on hand and in banks',
            ],
            [
                'code' => '1010',
                'name' => 'Petty Cash',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_code' => '1000',
            ],
            [
                'code' => '1100',
                'name' => 'Accounts Receivable',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Amounts owed by customers',
            ],
            [
                'code' => '1200',
                'name' => 'Inventory',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Goods held for sale',
            ],
            [
                'code' => '1300',
                'name' => 'Prepaid Expenses',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Expenses paid in advance',
            ],
            [
                'code' => '1500',
                'name' => 'Property, Plant and Equipment',
                'type' => 'asset',
                'sub_type' => 'non_current_asset',
                'description' => 'Fixed assets',
            ],
            [
                'code' => '1600',
                'name' => 'Accumulated Depreciation',
                'type' => 'asset',
                'sub_type' => 'non_current_asset',
                'parent_code' => '1500',
                'description' => 'Total depreciation of fixed assets',
            ],
            [
                'code' => '2000',
                'name' => 'Accounts Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Amounts owed to suppliers',
            ],
            [
                'code' => '2100',
                'name' => 'Accrued Expenses',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Expenses incurred but not yet paid',
            ],
            [
                'code' => '2200',
                'name' => 'Unearned Revenue',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Revenue received but not yet earned',
            ],
            [
                'code' => '2500',
                'name' => 'Long-term Liabilities',
                'type' => 'liability',
                'sub_type' => 'non_current_liability',
                'description' => 'Liabilities due beyond one year',
            ],
            [
                'code' => '3000',
                'name' => 'Owner Equity',
                'type' => 'equity',
                'sub_type' => 'equity',
                'description' => 'Owner investment in the business',
            ],
            [
                'code' => '3100',
                'name' => 'Retained Earnings',
                'type' => 'equity',
                'sub_type' => 'equity',
                'description' => 'Accumulated profits retained in the business',
            ],
            [
                'code' => '3200',
                'name' => 'Current Year Earnings',
                'type' => 'equity',
                'sub_type' => 'equity',
                'description' => 'Net income for the current period',
            ],
            [
                'code' => '4000',
                'name' => 'Sales Revenue',
                'type' => 'income',
                'sub_type' => 'operating_revenue',
                'description' => 'Revenue from primary business activities',
            ],
            [
                'code' => '4100',
                'name' => 'Service Revenue',
                'type' => 'income',
                'sub_type' => 'operating_revenue',
                'description' => 'Revenue from services rendered',
            ],
            [
                'code' => '4200',
                'name' => 'Other Income',
                'type' => 'income',
                'sub_type' => 'non_operating_revenue',
                'description' => 'Income from non-primary activities',
            ],
            [
                'code' => '5000',
                'name' => 'Cost of Goods Sold',
                'type' => 'expense',
                'sub_type' => 'cost_of_goods_sold',
                'description' => 'Direct costs of goods sold',
            ],
            [
                'code' => '6000',
                'name' => 'Salary Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Employee salaries and wages',
            ],
            [
                'code' => '6100',
                'name' => 'Rent Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Rental payments for office space',
            ],
            [
                'code' => '6200',
                'name' => 'Utilities Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Electricity, water, and other utilities',
            ],
            [
                'code' => '6300',
                'name' => 'Office Supplies Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Stationery and office materials',
            ],
            [
                'code' => '6400',
                'name' => 'Depreciation Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Depreciation of fixed assets',
            ],
            [
                'code' => '6500',
                'name' => 'Insurance Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Insurance premiums',
            ],
            [
                'code' => '6600',
                'name' => 'Professional Fees',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Legal, accounting, and consulting fees',
            ],
            [
                'code' => '7000',
                'name' => 'Interest Expense',
                'type' => 'expense',
                'sub_type' => 'non_operating_expense',
                'description' => 'Interest on borrowings',
            ],
        ];

        $createdAccounts = [];

        foreach ($accounts as $accountData) {
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
                'is_active' => true,
            ]);

            $createdAccounts[$accountData['code']] = $account->id;
        }
    }

    private function createSampleJournalEntries(Company $company, $branch, User $admin, User $accountant): void
    {
        $accounts = Account::where('company_id', $company->id)->get();
        $cash = $accounts->firstWhere('code', '1000');
        $ar = $accounts->firstWhere('code', '1100');
        $salesRevenue = $accounts->firstWhere('code', '4000');
        $serviceRevenue = $accounts->firstWhere('code', '4100');
        $rentExpense = $accounts->firstWhere('code', '6100');
        $utilitiesExpense = $accounts->firstWhere('code', '6200');
        $salaryExpense = $accounts->firstWhere('code', '6000');
        $accountsPayable = $accounts->firstWhere('code', '2000');
        $cogs = $accounts->firstWhere('code', '5000');

        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');

        $openPeriod = AccountingPeriod::where('company_id', $company->id)
            ->where('status', 'open')
            ->first();

        $entryDate = $openPeriod
            ? $openPeriod->start_date->addDays(5)->toDateString()
            : Carbon::create($year, $month, 5)->toDateString();

        $entries = [
            [
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 10000],
                ],
                'memo' => 'Cash sales revenue',
                'reference' => 'INV-001',
            ],
            [
                'lines' => [
                    ['account_id' => $ar->id, 'debit' => 5000, 'credit' => 0],
                    ['account_id' => $serviceRevenue->id, 'debit' => 0, 'credit' => 5000],
                ],
                'memo' => 'Service revenue on account',
                'reference' => 'INV-002',
            ],
            [
                'lines' => [
                    ['account_id' => $rentExpense->id, 'debit' => 2500, 'credit' => 0],
                    ['account_id' => $cash->id, 'debit' => 0, 'credit' => 2500],
                ],
                'memo' => 'Monthly office rent payment',
                'reference' => 'CHK-001',
            ],
            [
                'lines' => [
                    ['account_id' => $utilitiesExpense->id, 'debit' => 500, 'credit' => 0],
                    ['account_id' => $accountsPayable->id, 'debit' => 0, 'credit' => 500],
                ],
                'memo' => 'Utilities bill received',
                'reference' => 'UTIL-001',
            ],
            [
                'lines' => [
                    ['account_id' => $salaryExpense->id, 'debit' => 8000, 'credit' => 0],
                    ['account_id' => $cash->id, 'debit' => 0, 'credit' => 8000],
                ],
                'memo' => 'Biweekly payroll',
                'reference' => 'PAY-001',
            ],
            [
                'lines' => [
                    ['account_id' => $cogs->id, 'debit' => 3000, 'credit' => 0],
                    ['account_id' => $cash->id, 'debit' => 0, 'credit' => 3000],
                ],
                'memo' => 'Inventory purchase',
                'reference' => 'PO-001',
            ],
            [
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 7500, 'credit' => 0],
                    ['account_id' => $ar->id, 'debit' => 0, 'credit' => 7500],
                ],
                'memo' => 'Collection from customer',
                'reference' => 'REC-001',
            ],
        ];

        foreach ($entries as $index => $entryData) {
            $entryDateForEntry = Carbon::parse($entryDate)->addDays($index * 3)->toDateString();

            $periodForDate = AccountingPeriod::where('company_id', $company->id)
                ->where('start_date', '<=', $entryDateForEntry)
                ->where('end_date', '>=', $entryDateForEntry)
                ->first();

            if (!$periodForDate) {
                continue;
            }

            if ($periodForDate->isClosed() || $periodForDate->isLocked()) {
                continue;
            }

            $journalNumber = 'JE-' . $year . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            $userId = $index % 2 === 0 ? $admin->id : $accountant->id;

            $journalEntry = JournalEntry::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'journal_number' => $journalNumber,
                'date' => $entryDateForEntry,
                'reference' => $entryData['reference'],
                'memo' => $entryData['memo'],
                'status' => 'posted',
                'is_adjusting_entry' => false,
                'source_module' => 'seed',
                'created_by' => $userId,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            foreach ($entryData['lines'] as $lineData) {
                $journalEntry->lines()->create([
                    'account_id' => $lineData['account_id'],
                    'branch_id' => $branch->id,
                    'debit' => $lineData['debit'],
                    'credit' => $lineData['credit'],
                ]);
            }
        }
    }
}
