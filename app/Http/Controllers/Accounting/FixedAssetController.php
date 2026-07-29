<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\FixedAssetService;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function __construct(
        private FixedAssetService $service,
    ) {}

    public function index()
    {
        $companyId = session('current_company_id');

        $assets = Asset::where('company_id', $companyId)
            ->active()
            ->with('category')
            ->orderBy('asset_code')
            ->get();

        return view('accounting.fixed-assets.index', compact('assets'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $categories = AssetCategory::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('accounting.fixed-assets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'category_id' => 'required|exists:asset_categories,id',
            'asset_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'serial_number' => 'nullable|string|max:255',
            'acquisition_date' => 'required|date',
            'in_service_date' => 'required|date|after_or_equal:acquisition_date',
            'acquisition_cost' => 'required|numeric|min:0',
            'residual_value' => 'required|numeric|min:0',
            'useful_life' => 'required|integer|min:1',
            'depreciation_method_financial' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'depreciation_method_tax' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'useful_life_tax' => 'required|integer|min:1',
            'residual_value_tax' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'is_revaluation_enabled' => 'boolean',
            'asset_account_id' => 'nullable|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'nullable|exists:accounts,id',
            'depreciation_expense_account_id' => 'nullable|exists:accounts,id',
            'accumulated_impairment_account_id' => 'nullable|exists:accounts,id',
            'impairment_loss_account_id' => 'nullable|exists:accounts,id',
            'disposal_gain_loss_account_id' => 'nullable|exists:accounts,id',
            'revaluation_surplus_account_id' => 'nullable|exists:accounts,id',
        ]);

        $asset = $this->service->createAsset($companyId, $validated, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset)
            ->with('success', 'Fixed asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $this->authorizeCompanyAccess($asset);

        $asset->load([
            'category',
            'branch',
            'costCenter',
            'vendor',
            'depreciationBooks',
            'disposals',
            'transfers',
            'impairments',
            'revaluations',
            'usageEntries',
        ]);

        return view('accounting.fixed-assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $this->authorizeCompanyAccess($asset);

        $companyId = session('current_company_id');

        $categories = AssetCategory::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('accounting.fixed-assets.edit', compact('asset', 'categories'));
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorizeCompanyAccess($asset);

        $validated = $request->validate([
            'category_id' => 'required|exists:asset_categories,id',
            'asset_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'serial_number' => 'nullable|string|max:255',
            'acquisition_date' => 'required|date',
            'in_service_date' => 'required|date|after_or_equal:acquisition_date',
            'acquisition_cost' => 'required|numeric|min:0',
            'residual_value' => 'required|numeric|min:0',
            'useful_life' => 'required|integer|min:1',
            'depreciation_method_financial' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'depreciation_method_tax' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'useful_life_tax' => 'required|integer|min:1',
            'residual_value_tax' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'is_revaluation_enabled' => 'boolean',
            'asset_account_id' => 'nullable|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'nullable|exists:accounts,id',
            'depreciation_expense_account_id' => 'nullable|exists:accounts,id',
            'accumulated_impairment_account_id' => 'nullable|exists:accounts,id',
            'impairment_loss_account_id' => 'nullable|exists:accounts,id',
            'disposal_gain_loss_account_id' => 'nullable|exists:accounts,id',
            'revaluation_surplus_account_id' => 'nullable|exists:accounts,id',
        ]);

        $this->service->updateAsset($asset, $validated);

        return redirect()->route('accounting.fixed-assets.show', $asset)
            ->with('success', 'Fixed asset updated successfully.');
    }

    public function activate(Asset $asset)
    {
        $this->authorizeCompanyAccess($asset);

        $this->service->activateAsset($asset, auth()->id());

        return redirect()->route('accounting.fixed-assets.show', $asset)
            ->with('success', 'Fixed asset activated successfully.');
    }

    public function storeUsage(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'units_used' => 'required|numeric|min:0',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        $this->authorizeCompanyAccess($asset);

        $this->service->logUsage(
            $asset,
            $validated['period_start'],
            $validated['period_end'],
            $validated['units_used'],
            auth()->id(),
        );

        return redirect()->back()
            ->with('success', 'Usage entry logged successfully.');
    }

    private function authorizeCompanyAccess(Asset $asset): void
    {
        if ($asset->company_id !== session('current_company_id')) {
            abort(404);
        }
    }
}
