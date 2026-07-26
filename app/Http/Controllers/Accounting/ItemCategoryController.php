<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Services\Inventory\ItemCategoryService;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $categories = ItemCategory::where('company_id', $companyId)
            ->withCount('products')
            ->orderBy('name')
            ->paginate(20);

        return view('accounting.item-categories.index', compact('categories'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $accounts = \App\Models\Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.item-categories.create', compact('accounts'));
    }

    public function store(Request $request, ItemCategoryService $service)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'default_income_account_id' => 'nullable|exists:accounts,id',
            'default_cogs_account_id' => 'nullable|exists:accounts,id',
            'default_inventory_asset_account_id' => 'nullable|exists:accounts,id',
            'default_reorder_point' => 'nullable|numeric|min:0',
            'default_base_uom' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['company_id'] = $companyId;
            $validated['is_active'] = $request->boolean('is_active', true);

            $service->create($validated, $companyId);

            return redirect()->route('accounting.item-categories.index')
                ->with('success', 'Item category created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(ItemCategory $category)
    {
        $companyId = session('current_company_id');

        if ($category->company_id !== $companyId) {
            abort(404);
        }

        $category->load('products', 'defaultIncomeAccount', 'defaultCogsAccount', 'defaultInventoryAssetAccount');

        return view('accounting.item-categories.show', compact('category'));
    }

    public function edit(ItemCategory $category)
    {
        $companyId = session('current_company_id');

        if ($category->company_id !== $companyId) {
            abort(404);
        }

        $accounts = \App\Models\Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.item-categories.edit', compact('category', 'accounts'));
    }

    public function update(Request $request, ItemCategory $category, ItemCategoryService $service)
    {
        $companyId = session('current_company_id');

        if ($category->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'default_income_account_id' => 'nullable|exists:accounts,id',
            'default_cogs_account_id' => 'nullable|exists:accounts,id',
            'default_inventory_asset_account_id' => 'nullable|exists:accounts,id',
            'default_reorder_point' => 'nullable|numeric|min:0',
            'default_base_uom' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);

            $service->update($category, $validated);

            return redirect()->route('accounting.item-categories.index')
                ->with('success', 'Item category updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function toggle(ItemCategory $category, ItemCategoryService $service)
    {
        $companyId = session('current_company_id');

        if ($category->company_id !== $companyId) {
            abort(404);
        }

        $service->toggle($category);

        return back()->with('success', 'Category status toggled.');
    }
}
