<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Services\Admin\DefaultChartOfAccounts;
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
        $accounts = DefaultChartOfAccounts::get();

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
}

