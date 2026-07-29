<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $categories = AssetCategory::where('company_id', $companyId)
            ->with([
                'assetAccount',
                'accumulatedDepreciationAccount',
                'depreciationExpenseAccount',
                'accumulatedImpairmentAccount',
                'impairmentLossAccount',
                'disposalGainLossAccount',
                'revaluationSurplusAccount',
            ])
            ->orderBy('code')
            ->get();

        return view('accounting.asset-categories.index', compact('categories'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.asset-categories.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:asset_categories,code,NULL,id,company_id,' . $companyId,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'depreciation_method_financial' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'useful_life_financial' => 'required|integer|min:1',
            'residual_value_financial' => 'required|numeric|min:0',
            'depreciation_method_tax' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'useful_life_tax' => 'required|integer|min:1',
            'residual_value_tax' => 'required|numeric|min:0',
            'is_revaluation_enabled' => 'boolean',
            'asset_account_id' => 'required|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'required|exists:accounts,id',
            'depreciation_expense_account_id' => 'required|exists:accounts,id',
            'accumulated_impairment_account_id' => 'required|exists:accounts,id',
            'impairment_loss_account_id' => 'required|exists:accounts,id',
            'disposal_gain_loss_account_id' => 'required|exists:accounts,id',
            'revaluation_surplus_account_id' => 'required|exists:accounts,id',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        AssetCategory::create($validated);

        return redirect()->route('accounting.asset-categories.index')
            ->with('success', 'Asset category created successfully.');
    }

    public function show(AssetCategory $category)
    {
        $this->authorizeCompanyAccess($category);

        $category->load([
            'assetAccount',
            'accumulatedDepreciationAccount',
            'depreciationExpenseAccount',
            'accumulatedImpairmentAccount',
            'impairmentLossAccount',
            'disposalGainLossAccount',
            'revaluationSurplusAccount',
            'assets',
        ]);

        return view('accounting.asset-categories.show', compact('category'));
    }

    public function edit(AssetCategory $category)
    {
        $this->authorizeCompanyAccess($category);

        $companyId = session('current_company_id');

        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.asset-categories.edit', compact('category', 'accounts'));
    }

    public function update(Request $request, AssetCategory $category)
    {
        $this->authorizeCompanyAccess($category);

        $companyId = session('current_company_id');

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:asset_categories,code,' . $category->id . ',id,company_id,' . $companyId,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'depreciation_method_financial' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'useful_life_financial' => 'required|integer|min:1',
            'residual_value_financial' => 'required|numeric|min:0',
            'depreciation_method_tax' => 'required|in:straight_line,reducing_balance,double_declining,units_of_production,syd',
            'useful_life_tax' => 'required|integer|min:1',
            'residual_value_tax' => 'required|numeric|min:0',
            'is_revaluation_enabled' => 'boolean',
            'asset_account_id' => 'required|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'required|exists:accounts,id',
            'depreciation_expense_account_id' => 'required|exists:accounts,id',
            'accumulated_impairment_account_id' => 'required|exists:accounts,id',
            'impairment_loss_account_id' => 'required|exists:accounts,id',
            'disposal_gain_loss_account_id' => 'required|exists:accounts,id',
            'revaluation_surplus_account_id' => 'required|exists:accounts,id',
        ]);

        $category->update($validated);

        return redirect()->route('accounting.asset-categories.index')
            ->with('success', 'Asset category updated successfully.');
    }

    private function authorizeCompanyAccess(AssetCategory $category): void
    {
        if ($category->company_id !== session('current_company_id')) {
            abort(404);
        }
    }
}
