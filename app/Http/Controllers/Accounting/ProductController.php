<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Product;
use App\Models\InventoryStock;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Product::where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->with('incomeAccount', 'expenseAccount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('accounting.products.index', compact('products'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('type', 'income')
            ->active()
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('type', 'expense')
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.products.create', compact('incomeAccounts', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'products.create');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sku' => ['nullable', 'string', 'max:100', "unique:products,sku,NULL,id,company_id,{$companyId}"],
            'barcode' => ['nullable', 'string', 'max:100', "unique:products,barcode,NULL,id,company_id,{$companyId}"],
            'type' => ['required', 'string', 'in:service,inventory,non_inventory'],
            'sales_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'income_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'expense_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_taxable' => ['boolean'],
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;
        $validated['is_taxable'] = $request->boolean('is_taxable');
        $validated['tracked_as_inventory'] = ($validated['type'] === 'inventory');

        Product::create($validated);

        return redirect()->route('accounting.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $companyId = session('current_company_id');
        abort_unless($product->company_id == $companyId, 403);

        $product->load('incomeAccount', 'expenseAccount');

        return view('accounting.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $companyId = session('current_company_id');
        abort_unless($product->company_id == $companyId, 403);

        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('type', 'income')
            ->active()
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('type', 'expense')
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.products.edit', compact('product', 'incomeAccounts', 'expenseAccounts'));
    }

    public function update(Request $request, Product $product)
    {
        $this->requirePermission($request, 'products.edit');
        $companyId = session('current_company_id');
        abort_unless($product->company_id == $companyId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sku' => ['nullable', 'string', 'max:100', "unique:products,sku,{$product->id},id,company_id,{$companyId}"],
            'barcode' => ['nullable', 'string', 'max:100', "unique:products,barcode,{$product->id},id,company_id,{$companyId}"],
            'type' => ['required', 'string', 'in:service,inventory,non_inventory'],
            'sales_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'income_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'expense_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_taxable' => ['boolean'],
        ]);

        $validated['is_taxable'] = $request->boolean('is_taxable');
        $validated['tracked_as_inventory'] = ($validated['type'] === 'inventory');

        $product->update($validated);

        return redirect()->route('accounting.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function toggle(Product $product)
    {
        $this->requirePermission('products.void');
        $companyId = session('current_company_id');
        abort_unless($product->company_id == $companyId, 403);

        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.products.index')
            ->with('success', "Product {$status} successfully.");
    }

    public function search(Request $request)
    {
        $companyId = session('current_company_id');
        $search = $request->input('q', '');

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->when(strlen($search) > 0, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'barcode', 'sales_price', 'purchase_price', 'type', 'tracked_as_inventory', 'description']);

        $productIds = $products->pluck('id')->toArray();
        $stockByProduct = [];
        if (!empty($productIds)) {
            $stockRows = InventoryStock::where('company_id', $companyId)
                ->whereIn('product_id', $productIds)
                ->select('product_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity_on_hand) as qty'))
                ->groupBy('product_id')
                ->get();
            foreach ($stockRows as $row) {
                $stockByProduct[$row->product_id] = (float) $row->qty;
            }
        }

        $products->each(function ($product) use ($stockByProduct) {
            $product->stock_qty = $product->tracked_as_inventory
                ? ($stockByProduct[$product->id] ?? 0)
                : null;
        });

        return response()->json($products);
    }
}
