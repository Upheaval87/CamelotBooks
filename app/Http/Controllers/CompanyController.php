<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\CompanyAccessLog;
use App\Models\DefaultAccountMapping;
use App\Services\Admin\DefaultChartOfAccounts;
use App\Services\Admin\NumberingSequenceService;
use App\Services\Tenancy\CompanyAccessService;
use App\Services\Tenancy\CompanyProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CompanyController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $companies = $user->activeCompanyAssignments()->with('company')->get()
            ->map(fn ($assignment) => [
                'company' => $assignment->company,
                'role' => $assignment->role,
            ])
            ->filter(fn ($item) => $item['company'] !== null)
            ->values();

        if ($companies->isEmpty()) {
            $companies = $user->companies->map(fn ($company) => [
                'company' => $company,
                'role' => $company->pivot->role,
            ]);
        }

        $currencies = \App\Models\Currency::query()->active()->ordered()->get();

        return view('companies.index', compact('companies', 'currencies'));
    }

    public function select(Request $request, int $id)
    {
        $user = $request->user();

        $company = Company::query()->find($id);

        // Super admins enter any company as explicit, audited support access
        // (ACTION_SUPPORT below) and so are not bound to hasAccessToCompany().
        // A nonexistent/forged id still 404s via the falsy $company.
        if (!$company || (!$user->isSuperAdmin() && !$user->hasAccessToCompany($id))) {
            abort(404);
        }

        if (!$company->is_active) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'This company is no longer active.');
        }

        // Provisioned companies get the tenant connection bound on entry;
        // unprovisioned companies are entered in legacy mode (shared DB).
        $action = $user->isSuperAdmin() ? CompanyAccessLog::ACTION_SUPPORT : CompanyAccessLog::ACTION_SELECT;

        app(CompanyAccessService::class)->enter($user, $company, $action);

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
            'branch_limit' => 'required|integer|min:0',
            'accounting_method' => 'sometimes|string|in:accrual,cash',
            'reporting_preference' => 'sometimes|string|in:accrual_view,cash_view',
        ]);

        // Persist method + reporting preference with their spec defaults when
        // the creation surface omits them (back-compat with existing callers).
        $validated['accounting_method'] = $validated['accounting_method'] ?? Company::METHOD_ACCRUAL;
        $validated['reporting_preference'] = $validated['reporting_preference'] ?? Company::REPORTING_ACCRUAL_VIEW;

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

        if ($this->provisionCompany($company)) {
            app(CompanyAccessService::class)->enter($user, $company, CompanyAccessLog::ACTION_SELECT);

            return redirect()->route('dashboard')->with('success', 'Company created successfully.');
        }

        return redirect()->route('companies.index')->with(
            'success',
            'Company created. Provisioning will complete shortly — you will be able to select it once it is ready.'
        );
    }

    /**
     * Provision the new company's tenant database. Non-fatal: a provisioning
     * failure leaves the company in `pending` state so it can be retried later
     * via the provisioning command.
     */
    private function provisionCompany(Company $company): bool
    {
        try {
            app(CompanyProvisioningService::class)->provision($company);

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to provision company [{$company->id}] in the create-company flow: {$e->getMessage()}");

            return false;
        }
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

