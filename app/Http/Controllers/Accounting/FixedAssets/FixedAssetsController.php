<?php

namespace App\Http\Controllers\Accounting\FixedAssets;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaCategory;
use App\Models\FixedAssets\FaClass;
use App\Models\FixedAssets\FaDepMethod;
use App\Models\FixedAssets\FaDepRun;
use App\Models\FixedAssets\FaDisposal;
use App\Models\FixedAssets\FaTransfer;
use App\Models\FixedAssets\FaImpairment;
use App\Models\FixedAssets\FaRevaluation;
use App\Models\FixedAssets\FaVerification;
use App\Models\FixedAssets\FaHistory;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Vendor;
use App\Services\FixedAssets\AssetService;
use App\Services\FixedAssets\DepreciationService;
use App\Services\FixedAssets\DisposalService;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixedAssetsController extends Controller
{
    private function tenantConnection(): string
    {
        return TenantConnectionResolver::connectionName() ?? config('database.default');
    }

    private function companyId(): int
    {
        return (int) session('current_company_id');
    }

    // ── Asset Register ────────────────────────────

    public function register(Request $request)
    {
        $companyId = $this->companyId();

        $query = FaAsset::forCompany($companyId)->with(['category', 'branch']);

        if ($status = $request->input('status')) {
            $query->byStatus($status);
        }
        if ($categoryId = $request->input('category_id')) {
            $query->byCategory($categoryId);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $assets = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $categories = FaCategory::forCompany($companyId)->active()->orderBy('name')->get();

        $stats = [
            'total' => FaAsset::forCompany($companyId)->count(),
            'active' => FaAsset::forCompany($companyId)->active()->count(),
            'draft' => FaAsset::forCompany($companyId)->draft()->count(),
            'disposed' => FaAsset::forCompany($companyId)->where('status', 'disposed')->count(),
        ];

        return view('accounting.fixed-assets.register', compact('assets', 'categories', 'stats'));
    }

    // ── Asset CRUD ────────────────────────────────

    public function create()
    {
        $companyId = $this->companyId();

        return view('accounting.fixed-assets.create', [
            'categories' => FaCategory::forCompany($companyId)->active()->orderBy('name')->get(),
            'classes' => FaClass::forCompany($companyId)->active()->orderBy('name')->get(),
            'depMethods' => FaDepMethod::forCompany($companyId)->active()->orderBy('name')->get(),
            'branches' => Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'assetAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'fixed_asset')->where('is_active', true)->orderBy('code')->get(),
            'accumDepAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'accumulated_depreciation')->where('is_active', true)->orderBy('code')->get(),
            'depExpenseAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'depreciation_expense')->where('is_active', true)->orderBy('code')->get(),
            'disposalAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'disposal')->where('is_active', true)->orderBy('code')->get(),
            'vendors' => Vendor::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetService $service)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:fa_categories,id',
            'class_id' => 'nullable|exists:fa_classes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'serial_number' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'custodian' => 'nullable|string|max:255',
            'acquisition_date' => 'required|date',
            'in_service_date' => 'nullable|date|after_or_equal:acquisition_date',
            'acquisition_cost' => 'required|numeric|min:0',
            'residual_value' => 'nullable|numeric|min:0',
            'depreciation_method' => 'required|string|in:straight_line,declining_balance,sum_of_years,units_of_production',
            'useful_life' => 'required|integer|min:1',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'asset_account_id' => 'required|exists:accounts,id',
            'accum_dep_account_id' => 'required|exists:accounts,id',
            'dep_expense_account_id' => 'required|exists:accounts,id',
            'disposal_account_id' => 'nullable|exists:accounts,id',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $data['company_id'] = $this->companyId();

        $asset = $service->create($data, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', "Asset {$asset->asset_code} created successfully.");
    }

    public function show(int $id)
    {
        $asset = FaAsset::forCompany($this->companyId())
            ->with([
                'category', 'faClass', 'branch', 'costCenter',
                'assetAccount', 'accumDepAccount', 'depExpenseAccount', 'disposalAccount',
                'vendor', 'creator',
                'depBooks', 'components', 'acquisitions', 'transfers',
                'disposals', 'impairments', 'revaluations',
                'maintenanceRecords', 'insurancePolicies', 'warranties',
                'custodyRecords', 'documents',
            ])
            ->findOrFail($id);

        $history = FaHistory::forCompany($this->companyId())
            ->forAsset($asset->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('accounting.fixed-assets.show', compact('asset', 'history'));
    }

    public function edit(int $id)
    {
        $companyId = $this->companyId();
        $asset = FaAsset::forCompany($companyId)->findOrFail($id);

        return view('accounting.fixed-assets.edit', [
            'asset' => $asset,
            'categories' => FaCategory::forCompany($companyId)->active()->orderBy('name')->get(),
            'classes' => FaClass::forCompany($companyId)->active()->orderBy('name')->get(),
            'depMethods' => FaDepMethod::forCompany($companyId)->active()->orderBy('name')->get(),
            'branches' => Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'assetAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'fixed_asset')->where('is_active', true)->orderBy('code')->get(),
            'accumDepAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'accumulated_depreciation')->where('is_active', true)->orderBy('code')->get(),
            'depExpenseAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'depreciation_expense')->where('is_active', true)->orderBy('code')->get(),
            'disposalAccounts' => Account::where('company_id', $companyId)->where('sub_type', 'disposal')->where('is_active', true)->orderBy('code')->get(),
            'vendors' => Vendor::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'category_id' => 'required|exists:fa_categories,id',
            'class_id' => 'nullable|exists:fa_classes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'serial_number' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'custodian' => 'nullable|string|max:255',
            'in_service_date' => 'nullable|date',
            'residual_value' => 'nullable|numeric|min:0',
            'depreciation_method' => 'required|string|in:straight_line,declining_balance,sum_of_years,units_of_production',
            'useful_life' => 'required|integer|min:1',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'asset_account_id' => 'required|exists:accounts,id',
            'accum_dep_account_id' => 'required|exists:accounts,id',
            'dep_expense_account_id' => 'required|exists:accounts,id',
            'disposal_account_id' => 'nullable|exists:accounts,id',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $asset = $service->update($asset, $data);

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Asset updated successfully.');
    }

    public function activate(int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);
        $service->activate($asset, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Asset activated successfully.');
    }

    public function destroy(int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);
        $service->destroy($asset);

        return redirect()->route('accounting.fixed-assets.register')
            ->with('success', 'Asset deleted successfully.');
    }

    // ── Categories ────────────────────────────────

    public function categories()
    {
        $companyId = $this->companyId();

        $categories = FaCategory::forCompany($companyId)
            ->withCount(['assets' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->paginate(20);

        return view('accounting.fixed-assets.categories', compact('categories'));
    }

    public function storeCategory(Request $request, AssetService $service)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:fa_categories,code,' . $this->companyId() . ',company_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $data['company_id'] = $this->companyId();
        $service->createCategory($data);

        return redirect()->route('accounting.fixed-assets.categories')
            ->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, int $id, AssetService $service)
    {
        $category = FaCategory::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'code' => 'required|string|max:20|unique:fa_categories,code,' . $category->id . ',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $service->updateCategory($category, $data);

        return redirect()->route('accounting.fixed-assets.categories')
            ->with('success', 'Category updated successfully.');
    }

    public function toggleCategory(int $id, AssetService $service)
    {
        $category = FaCategory::forCompany($this->companyId())->findOrFail($id);
        $service->toggleCategory($category);

        return redirect()->route('accounting.fixed-assets.categories')
            ->with('success', 'Category status toggled.');
    }

    // ── Classes ──────────────────────────────────

    public function classes()
    {
        $companyId = $this->companyId();

        $classes = FaClass::forCompany($companyId)
            ->withCount(['assets' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->paginate(20);

        return view('accounting.fixed-assets.classes', compact('classes'));
    }

    public function storeClass(Request $request, AssetService $service)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:fa_classes,code,' . $this->companyId() . ',company_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'default_dep_method' => 'nullable|string|max:50',
            'default_useful_life' => 'nullable|integer|min:1|max:600',
            'default_residual_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $data['company_id'] = $this->companyId();
        $service->createClass($data);

        return redirect()->route('accounting.fixed-assets.classes')
            ->with('success', 'Class created successfully.');
    }

    public function updateClass(Request $request, int $id, AssetService $service)
    {
        $class = FaClass::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'code' => 'required|string|max:20|unique:fa_classes,code,' . $class->id . ',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'default_dep_method' => 'nullable|string|max:50',
            'default_useful_life' => 'nullable|integer|min:1|max:600',
            'default_residual_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $service->updateClass($class, $data);

        return redirect()->route('accounting.fixed-assets.classes')
            ->with('success', 'Class updated successfully.');
    }

    public function toggleClass(int $id, AssetService $service)
    {
        $class = FaClass::forCompany($this->companyId())->findOrFail($id);
        $service->toggleCategory($class);

        return redirect()->route('accounting.fixed-assets.classes')
            ->with('success', 'Class status toggled.');
    }

    // ── Depreciation Methods ─────────────────────

    public function depMethods()
    {
        $companyId = $this->companyId();

        $methods = FaDepMethod::forCompany($companyId)
            ->orderBy('name')
            ->paginate(20);

        return view('accounting.fixed-assets.dep-methods', compact('methods'));
    }

    public function storeDepMethod(Request $request, AssetService $service)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:fa_dep_methods,code,' . $this->companyId() . ',company_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'calculator_class' => 'nullable|string|max:255',
        ]);

        $data['company_id'] = $this->companyId();
        $service->createDepMethod($data);

        return redirect()->route('accounting.fixed-assets.dep-methods')
            ->with('success', 'Depreciation method created successfully.');
    }

    public function updateDepMethod(Request $request, int $id, AssetService $service)
    {
        $method = FaDepMethod::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'code' => 'required|string|max:20|unique:fa_dep_methods,code,' . $method->id . ',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'calculator_class' => 'nullable|string|max:255',
        ]);

        $service->updateDepMethod($method, $data);

        return redirect()->route('accounting.fixed-assets.dep-methods')
            ->with('success', 'Depreciation method updated successfully.');
    }

    public function toggleDepMethod(int $id, AssetService $service)
    {
        $method = FaDepMethod::forCompany($this->companyId())->findOrFail($id);
        $service->toggleCategory($method);

        return redirect()->route('accounting.fixed-assets.dep-methods')
            ->with('success', 'Depreciation method status toggled.');
    }

    // ── Components ───────────────────────────────

    public function addComponent(Request $request, int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'cost' => 'required|numeric|min:0',
            'depreciation_method' => 'nullable|string|max:50',
            'useful_life' => 'nullable|integer|min:1|max:600',
            'residual_value' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
        ]);

        $data['status'] = 'active';
        $data['net_book_value'] = $data['cost'];
        $data['accumulated_depreciation'] = 0;
        $service->addComponent($asset, $data);

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Component added successfully.');
    }

    public function removeComponent(int $id, AssetService $service)
    {
        $component = \App\Models\FixedAssets\FaComponent::where('company_id', $this->companyId())->findOrFail($id);
        $assetId = $component->asset_id;
        $service->removeComponent($component);

        return redirect()->route('accounting.fixed-assets.show', $assetId)
            ->with('success', 'Component removed.');
    }

    // ── Maintenance ──────────────────────────────

    public function storeMaintenance(Request $request, int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'type' => 'required|string|in:scheduled,unscheduled,repair',
            'maintenance_date' => 'required|date',
            'next_due_date' => 'nullable|date|after:maintenance_date',
            'provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:5000',
            'work_performed' => 'nullable|string|max:5000',
        ]);

        $data['status'] = 'completed';
        $service->createMaintenance($asset, $data, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Maintenance record added.');
    }

    // ── Insurance ────────────────────────────────

    public function storeInsurance(Request $request, int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'policy_number' => 'required|string|max:100',
            'provider' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'coverage_amount' => 'nullable|numeric|min:0',
            'annual_premium' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $service->createInsurance($asset, $data);

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Insurance policy added.');
    }

    // ── Warranty ─────────────────────────────────

    public function storeWarranty(Request $request, int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'provider' => 'required|string|max:255',
            'warranty_number' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'terms' => 'nullable|string|max:5000',
            'contact_info' => 'nullable|string|max:5000',
        ]);

        $service->createWarranty($asset, $data);

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Warranty added.');
    }

    // ── Custody ──────────────────────────────────

    public function storeCustody(Request $request, int $id, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($id);

        $data = $request->validate([
            'from_custodian' => 'nullable|string|max:255',
            'to_custodian' => 'required|string|max:255',
            'handover_date' => 'required|date',
            'reason' => 'nullable|string|max:5000',
            'condition_notes' => 'nullable|string|max:5000',
        ]);

        $service->createCustody($asset, $data, auth()->id());
        $asset->update(['custodian' => $data['to_custodian']]);

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Custody transfer recorded.');
    }

    // ── Verifications ────────────────────────────

    public function verifications()
    {
        $companyId = $this->companyId();

        $verifications = FaVerification::forCompany($companyId)
            ->with('branch', 'assignee')
            ->orderByDesc('scheduled_date')
            ->paginate(20);

        return view('accounting.fixed-assets.verifications', compact('verifications'));
    }

    public function createVerification()
    {
        $branches = \App\Models\Branch::where('company_id', $this->companyId())->where('is_active', true)->orderBy('name')->get();

        return view('accounting.fixed-assets.create-verification', compact('branches'));
    }

    public function storeVerification(Request $request, AssetService $service)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'scheduled_date' => 'required|date',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $service->createVerification($data, $companyId);

        return redirect()->route('accounting.fixed-assets.verifications')
            ->with('success', 'Verification scheduled.');
    }

    // ── Depreciation Runs ─────────────────────────

    public function depreciationRuns()
    {
        $companyId = $this->companyId();

        $runs = FaDepRun::forCompany($companyId)
            ->with('runner', 'approver')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting.fixed-assets.depreciation-runs', compact('runs'));
    }

    public function createDepreciationRun()
    {
        $companyId = $this->companyId();
        $activeAssets = FaAsset::forCompany($companyId)->active()->count();

        return view('accounting.fixed-assets.create-depreciation-run', [
            'activeAssets' => $activeAssets,
        ]);
    }

    public function storeDepreciationRun(Request $request, DepreciationService $depService)
    {
        $data = $request->validate([
            'period' => 'required|string|max:50',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'book_type' => 'required|string|in:financial,tax',
        ]);

        $run = $depService->createRun(
            $this->companyId(),
            $data['period'],
            $data['period_start'],
            $data['period_end'],
            $data['book_type'],
            auth()->id()
        );

        return redirect()->route('accounting.fixed-assets.depreciation-runs')
            ->with('success', "Depreciation run {$run->run_number} created with {$run->asset_count} assets.");
    }

    public function postDepreciationRun(int $id, DepreciationService $depService)
    {
        $run = FaDepRun::forCompany($this->companyId())->findOrFail($id);
        $depService->postRun($run);

        return redirect()->route('accounting.fixed-assets.depreciation-runs')
            ->with('success', "Depreciation run {$run->run_number} posted successfully.");
    }

    public function reverseDepreciationRun(int $id, DepreciationService $depService)
    {
        $run = FaDepRun::forCompany($this->companyId())->findOrFail($id);
        $depService->reverseRun($run);

        return redirect()->route('accounting.fixed-assets.depreciation-runs')
            ->with('success', "Depreciation run {$run->run_number} reversed successfully.");
    }

    // ── Disposals ─────────────────────────────────

    public function disposals()
    {
        $companyId = $this->companyId();

        $disposals = FaDisposal::forCompany($companyId)
            ->with(['asset', 'requester', 'approver'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting.fixed-assets.disposals', compact('disposals'));
    }

    public function createDisposal(int $assetId)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        return view('accounting.fixed-assets.create-disposal', compact('asset'));
    }

    public function storeDisposal(Request $request, int $assetId, DisposalService $dispService)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        $data = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_method' => 'required|string|in:sale,scrap,donation,destroyed',
            'proceeds_amount' => 'nullable|numeric|min:0',
            'disposal_cost' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:5000',
        ]);

        $dispService->createDisposal($asset, $data, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Disposal request created successfully.');
    }

    public function approveDisposal(int $id, DisposalService $dispService)
    {
        $disposal = FaDisposal::forCompany($this->companyId())->findOrFail($id);
        $dispService->approveDisposal($disposal, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $disposal->asset_id)
            ->with('success', 'Disposal approved. Asset has been disposed.');
    }

    public function rejectDisposal(int $id, DisposalService $dispService)
    {
        $disposal = FaDisposal::forCompany($this->companyId())->findOrFail($id);
        $dispService->rejectDisposal($disposal, auth()->id());

        return redirect()->route('accounting.fixed-assets.disposals')
            ->with('success', 'Disposal rejected.');
    }

    // ── Transfers ─────────────────────────────────

    public function transfers()
    {
        $companyId = $this->companyId();

        $transfers = FaTransfer::forCompany($companyId)
            ->with(['asset', 'fromBranch', 'toBranch', 'requester', 'approver'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting.fixed-assets.transfers', compact('transfers'));
    }

    public function createTransfer(int $assetId)
    {
        $companyId = $this->companyId();
        $asset = FaAsset::forCompany($companyId)->findOrFail($assetId);

        return view('accounting.fixed-assets.create-transfer', [
            'asset' => $asset,
            'branches' => Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeTransfer(Request $request, int $assetId, AssetService $service)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        $data = $request->validate([
            'transfer_date' => 'required|date',
            'to_branch_id' => 'nullable|exists:branches,id',
            'to_cost_center_id' => 'nullable|exists:cost_centers,id',
            'to_custodian' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:5000',
        ]);

        $service->transfer($asset, $data, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Transfer request created successfully.');
    }

    public function approveTransfer(int $id, AssetService $service)
    {
        $transfer = FaTransfer::forCompany($this->companyId())->findOrFail($id);
        $service->approveTransfer($transfer, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $transfer->asset_id)
            ->with('success', 'Transfer approved. Asset location updated.');
    }

    public function rejectTransfer(int $id, AssetService $service)
    {
        $transfer = FaTransfer::forCompany($this->companyId())->findOrFail($id);
        $service->rejectTransfer($transfer, auth()->id());

        return redirect()->route('accounting.fixed-assets.transfers')
            ->with('success', 'Transfer rejected.');
    }

    // ── Impairments ───────────────────────────────

    public function impairments()
    {
        $companyId = $this->companyId();

        $impairments = FaImpairment::forCompany($companyId)
            ->with(['asset', 'requester', 'approver'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting.fixed-assets.impairments', compact('impairments'));
    }

    public function createImpairment(int $assetId)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        return view('accounting.fixed-assets.create-impairment', compact('asset'));
    }

    public function storeImpairment(Request $request, int $assetId, DisposalService $dispService)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        $data = $request->validate([
            'impairment_date' => 'required|date',
            'recoverable_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:5000',
        ]);

        $dispService->createImpairment($asset, $data, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Impairment recorded successfully.');
    }

    public function approveImpairment(int $id, DisposalService $dispService)
    {
        $impairment = FaImpairment::forCompany($this->companyId())->findOrFail($id);
        $dispService->approveImpairment($impairment, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $impairment->asset_id)
            ->with('success', 'Impairment approved.');
    }

    // ── Revaluations ──────────────────────────────

    public function revaluations()
    {
        $companyId = $this->companyId();

        $revaluations = FaRevaluation::forCompany($companyId)
            ->with(['asset', 'requester', 'approver'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting.fixed-assets.revaluations', compact('revaluations'));
    }

    public function createRevaluation(int $assetId)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        return view('accounting.fixed-assets.create-revaluation', compact('asset'));
    }

    public function storeRevaluation(Request $request, int $assetId, DisposalService $dispService)
    {
        $asset = FaAsset::forCompany($this->companyId())->findOrFail($assetId);

        $data = $request->validate([
            'revaluation_date' => 'required|date',
            'new_value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:5000',
        ]);

        $dispService->createRevaluation($asset, $data, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset->id)
            ->with('success', 'Revaluation request created successfully.');
    }

    public function approveRevaluation(int $id, DisposalService $dispService)
    {
        $revaluation = FaRevaluation::forCompany($this->companyId())->findOrFail($id);
        $dispService->approveRevaluation($revaluation, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $revaluation->asset_id)
            ->with('success', 'Revaluation approved. Net book value updated.');
    }

    public function rejectRevaluation(int $id, DisposalService $dispService)
    {
        $revaluation = FaRevaluation::forCompany($this->companyId())->findOrFail($id);
        $dispService->rejectRevaluation($revaluation, auth()->id());

        return redirect()->route('accounting.fixed-assets.revaluations')
            ->with('success', 'Revaluation rejected.');
    }
}
