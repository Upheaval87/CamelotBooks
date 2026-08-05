<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\SuperAdminAuditLog;
use App\Services\SuperAdmin\TenantBranchReader;
use App\Services\Tenancy\CompanyProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompaniesController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->withCount([
                'companyModules as active_modules_count' => fn ($q) => $q->where('is_active', true),
                'assignments as assignment_count',
            ])
            ->orderBy('name')
            ->get();

        return view('superadmin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('superadmin.companies.create');
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

        $company = Company::create($validated + [
            'is_active' => true,
            'provisioning_status' => Company::STATUS_PENDING,
        ]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_COMPANY_CREATED,
            $company->id,
            'company',
            $company->id,
            null,
            ['name' => $company->name, 'company_code' => $company->company_code, 'base_currency' => $company->base_currency],
            "Company '{$company->name}' created."
        );

        try {
            app(CompanyProvisioningService::class)->provision($company);
        } catch (\Throwable $e) {
            Log::error("Super Admin provisioning failed for company [{$company->id}]: {$e->getMessage()}");

            SuperAdminAuditLog::log(
                $request->user()->id,
                SuperAdminAuditLog::ACTION_COMPANY_PROVISION_FAILED,
                $company->id,
                'company',
                $company->id,
                null,
                ['error' => Str::limit($e->getMessage(), 500)],
                'Tenant database provisioning failed.'
            );

            return redirect()->route('superadmin.companies.show', $company)
                ->with('error', 'Company created, but tenant provisioning failed. Check the provisioning status and retry.');
        }

        return redirect()->route('superadmin.companies.show', $company)
            ->with('success', 'Company created and provisioned successfully.');
    }

    public function show(Company $company)
    {
        $modules = Module::query()->orderBy('sort_order')->get();
        $moduleStates = CompanyModule::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('module_id');
        $assignments = $company->assignments()->with('user')->orderBy('id')->get();
        $audit = SuperAdminAuditLog::query()
            ->where('company_id', $company->id)
            ->with('user')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('superadmin.companies.show', compact('company', 'modules', 'moduleStates', 'assignments', 'audit'));
    }

    public function suspend(Request $request, Company $company)
    {
        if (! $company->is_active) {
            return back()->with('info', 'Company is already suspended.');
        }

        $company->update(['is_active' => false]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_COMPANY_SUSPENDED,
            $company->id,
            'company',
            $company->id,
            ['is_active' => true],
            ['is_active' => false],
            "Company '{$company->name}' suspended."
        );

        return redirect()->route('superadmin.companies.show', $company)->with('success', 'Company suspended.');
    }

    public function reactivate(Request $request, Company $company)
    {
        if ($company->is_active) {
            return back()->with('info', 'Company is already active.');
        }

        $company->update(['is_active' => true]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_COMPANY_REACTIVATED,
            $company->id,
            'company',
            $company->id,
            ['is_active' => false],
            ['is_active' => true],
            "Company '{$company->name}' reactivated."
        );

        return redirect()->route('superadmin.companies.show', $company)->with('success', 'Company reactivated.');
    }

    public function modules(Company $company)
    {
        $modules = Module::query()->orderBy('sort_order')->get();
        $moduleStates = CompanyModule::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('module_id');

        return view('superadmin.companies.modules', compact('company', 'modules', 'moduleStates'));
    }

    public function toggleModule(Request $request, Company $company, Module $module)
    {
        abort_unless(! $module->is_core, 403);

        $existing = CompanyModule::query()
            ->where('company_id', $company->id)
            ->where('module_id', $module->id)
            ->first();
        $wasActive = (bool) ($existing?->is_active);

        CompanyModule::updateOrCreate(
            ['company_id' => $company->id, 'module_id' => $module->id],
            [
                'is_active' => ! $wasActive,
                'activated_at' => $wasActive ? $existing->activated_at : now(),
                'activated_by' => $wasActive ? $existing->activated_by : $request->user()->id,
                'updated_at' => now(),
            ]
        );

        SuperAdminAuditLog::log(
            $request->user()->id,
            $wasActive ? SuperAdminAuditLog::ACTION_MODULE_DISABLED : SuperAdminAuditLog::ACTION_MODULE_ENABLED,
            $company->id,
            'module',
            $module->id,
            ['is_active' => $wasActive],
            ['is_active' => ! $wasActive],
            "Module '{$module->name}' ".($wasActive ? 'disabled for' : 'enabled for')." '{$company->name}'."
        );

        return redirect()->route('superadmin.companies.modules', $company)
            ->with('success', "{$module->name} ".($wasActive ? 'disabled' : 'enabled')." for {$company->name}.");
    }

    public function dbPreview(Request $request)
    {
        $name = $request->validate(['name' => 'required|string|max:255'])['name'];

        return response()->json([
            'db_name' => app(CompanyProvisioningService::class)->previewDatabaseName($name),
        ]);
    }

    public function branches(Company $company)
    {
        return response()->json(app(TenantBranchReader::class)->branchesFor($company));
    }
}
