<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = auth()->user()->companies;

        return view('companies.index', compact('companies'));
    }

    public function select(int $id)
    {
        $company = auth()->user()->companies()->findOrFail($id);

        Session::put('current_company_id', $company->id);

        return redirect()->route('dashboard');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'company_code' => 'nullable|string|max:50|unique:companies,company_code',
            'tax_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'base_currency' => 'required|string|max:10',
            'fiscal_year_start_month' => 'required|integer|min:1|max:12',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($validated, $user, &$company) {
            $company = Company::create($validated);

            $user->companies()->attach($company->id, ['role' => 'company_admin']);

            setPermissionsTeamId($company->id);
            $user->assignRole('company_admin');

            $this->createAccountingPeriods($company);

            $createdAccounts = $this->copyDefaultChartOfAccounts($company);

            $this->seedDefaultAccountMappings($company, $createdAccounts);

            ApprovalSetting::create([
                'company_id' => $company->id,
                'requires_approval' => false,
                'threshold_amount' => 0,
            ]);

            foreach (\App\Models\ApprovalThreshold::documentTypes() as $type => $label) {
                \App\Models\ApprovalThreshold::create([
                    'company_id' => $company->id,
                    'document_type' => $type,
                    'threshold_amount' => 0,
                    'is_active' => false,
                ]);
            }

            app(NumberingSequenceService::class)->seedDefaults($company->id);
        });

        Session::put('current_company_id', $company->id);

        return redirect()->route('dashboard')->with('success', 'Company created successfully.');
    }

    public function update(Request $request, Company $company)
    {
        $user = $request->user();

        $currentTeamId = getPermissionsTeamId();
        setPermissionsTeamId($company->id);
        abort_unless($user->hasRole('company_admin'), 403);
        setPermissionsTeamId($currentTeamId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'company_code' => 'nullable|string|max:50|unique:companies,company_code,' . $company->id,
            'tax_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'base_currency' => 'required|string|max:10',
            'fiscal_year_start_month' => 'required|integer|min:1|max:12',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
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
        $accounts = $this->getDefaultAccounts();

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
                'is_bank_account' => ($accountData['code'] === '1000'),
                'is_active' => true,
            ]);

            $createdAccounts[$accountData['code']] = $account->id;
        }

        return $createdAccounts;
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

    private function getDefaultAccounts(): array
    {
        return [
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
                'code' => '1150',
                'name' => 'Tax Receivable',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Input tax / VAT receivable',
            ],
            [
                'code' => '1200',
                'name' => 'Inventory',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'description' => 'Inventory on hand at cost',
            ],
            [
                'code' => '1500',
                'name' => 'Property, Plant and Equipment',
                'type' => 'asset',
                'sub_type' => 'fixed_asset',
                'description' => 'Fixed assets',
            ],
            [
                'code' => '1600',
                'name' => 'Accumulated Depreciation',
                'type' => 'asset',
                'sub_type' => 'fixed_asset',
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
                'code' => '2300',
                'name' => 'Sales Tax Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Sales tax / VAT collected',
            ],
            [
                'code' => '2400',
                'name' => 'PAYE Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Income tax deducted from employees',
            ],
            [
                'code' => '2410',
                'name' => 'Pension Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Employee and employer pension contributions',
            ],
            [
                'code' => '2420',
                'name' => 'Net Pay Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'description' => 'Salaries owed to employees',
            ],
            [
                'code' => '2500',
                'name' => 'Long-term Liabilities',
                'type' => 'liability',
                'sub_type' => 'long_term_liability',
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
                'sub_type' => 'revenue',
                'description' => 'Revenue from primary business activities',
            ],
            [
                'code' => '4100',
                'name' => 'Service Revenue',
                'type' => 'income',
                'sub_type' => 'revenue',
                'description' => 'Revenue from services rendered',
            ],
            [
                'code' => '4200',
                'name' => 'Other Income',
                'type' => 'income',
                'sub_type' => 'other_income',
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
                'code' => '6010',
                'name' => 'Employer Pension Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'description' => 'Employer contribution to pension scheme',
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
                'code' => '6700',
                'name' => 'Inventory Adjustment',
                'type' => 'expense',
                'sub_type' => 'cost_of_goods_sold',
                'description' => 'Stock adjustments, shrinkage, and write-offs',
            ],
            [
                'code' => '7000',
                'name' => 'Interest Expense',
                'type' => 'expense',
                'sub_type' => 'other_expense',
                'description' => 'Interest on borrowings',
            ],
            [
                'code' => '7200',
                'name' => 'Realized FX Gain/Loss',
                'type' => 'expense',
                'sub_type' => 'other_expense',
                'description' => 'Realized foreign exchange gains and losses',
            ],
            [
                'code' => '7300',
                'name' => 'Unrealized FX Gain/Loss',
                'type' => 'expense',
                'sub_type' => 'other_expense',
                'description' => 'Unrealized foreign exchange gains and losses on revaluation',
            ],
            [
                'code' => '9999',
                'name' => 'Rounding Differences',
                'type' => 'expense',
                'sub_type' => 'other_expense',
                'description' => 'Rounding differences on multi-line transactions',
            ],
        ];
    }
}
