<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ItemCategory;
use App\Models\ItemReturnable;
use App\Models\Product;
use App\Models\InventoryAdjustment;
use App\Models\InventoryTransfer;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\SystemSetting;

class InventoryCentreController extends Controller
{
    public function dashboard()
    {
        $companyId = session('current_company_id');
        $products = Product::forCompany($companyId)->active();
        $totalProducts = $products->count();
        $trackedProducts = (clone $products)->where('tracked_as_inventory', true)->count();

        $categories = ItemCategory::forCompany($companyId)->count();

        $recentAdjustments = InventoryAdjustment::forCompany($companyId)
            ->latest()->take(5)->get(['id', 'adjustment_number', 'status', 'created_at']);

        $recentTransfers = InventoryTransfer::forCompany($companyId)
            ->latest()->take(5)->get(['id', 'transfer_number', 'status', 'created_at']);

        $lowStockCount = DB::table('products')
            ->where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->where('reorder_point', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE product_id = products.id) <= reorder_point')
            ->count();

        $valuationTotal = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->selectRaw('SUM(quantity_remaining * unit_cost) as total_value')
            ->value('total_value') ?? 0;

        $categoriesList = ItemCategory::forCompany($companyId)->orderBy('name')->get(['id', 'name']);

        $topItems = DB::table('products')
            ->where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->orderByDesc('sales_price')
            ->take(5)
            ->get(['id', 'name', 'sku', 'sales_price']);

        $movementData = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('products', function ($j) {
                $j->on('journal_entry_lines.entity_id', '=', 'products.id')
                  ->where('journal_entry_lines.entity_type', '=', Product::class);
            })
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->selectRaw('products.name as product_name, SUM(journal_entry_lines.debit) as total_debit, SUM(journal_entry_lines.credit) as total_credit')
            ->groupBy('products.name')
            ->orderByDesc('total_debit')
            ->take(8)
            ->get();

        return view('accounting.inventory.dashboard', compact(
            'totalProducts', 'trackedProducts', 'categories', 'lowStockCount',
            'valuationTotal', 'recentAdjustments', 'recentTransfers',
            'categoriesList', 'topItems', 'movementData'
        ));
    }

    public function items(Request $request)
    {
        $companyId = session('current_company_id');
        $query = $this->baseItemsQuery($request, $companyId);

        $products = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = ItemCategory::forCompany($companyId)->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => Product::forCompany($companyId)->count(),
            'active' => Product::forCompany($companyId)->active()->count(),
            'tracked' => Product::forCompany($companyId)->where('tracked_as_inventory', true)->count(),
            'low_stock' => DB::table('products')
                ->where('company_id', $companyId)
                ->where('tracked_as_inventory', true)
                ->whereRaw('reorder_point > 0 AND (SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE product_id = products.id) <= reorder_point')
                ->count(),
        ];

        $cs = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        return view('accounting.inventory.items', compact('products', 'categories', 'stats', 'cs'));
    }

    public function itemsExportCsv(Request $request)
    {
        $this->requirePermission($request, 'inventory.view');

        $companyId = session('current_company_id');
        $products = $this->baseItemsQuery($request, $companyId)->orderBy('name')->get();

        $filename = 'inventory-items-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'SKU', 'Barcode', 'Category', 'Type', 'Sales Price', 'Purchase Price', 'Stock', 'Reorder Point', 'Tracked', 'Status']);
            foreach ($products as $p) {
                fputcsv($out, [
                    $p->name,
                    $p->sku,
                    $p->barcode,
                    $p->itemCategory?->name ?? '',
                    ucfirst($p->type),
                    number_format((float) $p->sales_price, 2, '.', ''),
                    number_format((float) $p->purchase_price, 2, '.', ''),
                    $p->tracked_as_inventory ? $p->stock_qty : 'N/A',
                    $p->effective_reorder_point ?? '',
                    $p->tracked_as_inventory ? 'Yes' : 'No',
                    $p->is_active ? 'Active' : 'Inactive',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function baseItemsQuery(Request $request, int $companyId)
    {
        $query = Product::forCompany($companyId)->with('itemCategory');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%")
                  ->orWhere('barcode', 'like', "%{$q}%");
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $request->status === 'active' ? $query->active() : $query->where('is_active', false);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('tracked')) {
            $query->where('tracked_as_inventory', $request->tracked === 'yes');
        }

        return $query;
    }

    public function itemsCreate()
    {
        $companyId = session('current_company_id');
        $categories = ItemCategory::forCompany($companyId)->orderBy('name')->get(['id', 'name']);
        $incomeAccounts = Account::forCompany($companyId)->whereIn('sub_type', ['income', 'revenue'])->orderBy('code')->get(['id', 'code', 'name']);
        $expenseAccounts = Account::forCompany($companyId)->where('sub_type', 'cost_of_goods_sold')->orderBy('code')->get(['id', 'code', 'name']);
        $inventoryAccounts = Account::forCompany($companyId)->where('sub_type', 'current_asset')->orderBy('code')->get(['id', 'code', 'name']);
        $suppliers = Vendor::forCompany($companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $warehouses = \App\Models\Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('accounting.inventory.items-create', compact(
            'categories', 'incomeAccounts', 'expenseAccounts', 'inventoryAccounts', 'suppliers', 'warehouses'
        ));
    }

    public function itemsStore(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->where('company_id', $companyId)],
            'barcode' => 'nullable|string|max:50',
            'type' => 'required|in:goods,service,bundle',
            'brand' => 'nullable|string|max:100',
            'item_category_id' => 'nullable|exists:item_categories,id',
            'unit_of_measure' => 'nullable|string|max:20',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string|max:5000',

            'sales_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'income_account_id' => 'nullable|exists:accounts,id',
            'expense_account_id' => 'nullable|exists:accounts,id',
            'inventory_asset_account_id' => 'nullable|exists:accounts,id',
            'price_list' => 'nullable|string|max:50',

            'tracked_as_inventory' => 'boolean',
            'opening_stock' => 'nullable|numeric|min:0',
            'opening_as_at' => 'nullable|date',
            'warehouse_id' => 'nullable|exists:branches,id',
            'max_stock' => 'nullable|integer|min:0',
            'reorder_qty' => 'nullable|integer|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'default_supplier_id' => 'nullable|exists:vendors,id',
            'costing_method' => 'nullable|in:weighted_average,fifo',
            'low_stock_alerts' => 'boolean',
            'batch_expiry_tracking' => 'boolean',
            'serial_tracking' => 'boolean',

            'is_returnable' => 'boolean',
            'container_type' => 'required_if:is_returnable,1|nullable|in:bottle,crate,keg,cylinder',
            'deposit_value' => 'required_if:is_returnable,1|nullable|numeric|min:0',
            'deposit_tax_handling' => 'nullable|in:excluded,taxed',
            'return_window_days' => 'nullable|integer|min:1',
            'linked_empty_item_id' => 'nullable|exists:products,id',
            'linked_filled_item_id' => 'nullable|exists:products,id',
            'required_return' => 'nullable|in:one_to_one,free',
            'container_stock_account_id' => 'nullable|exists:accounts,id',
            'container_stock_tracking' => 'boolean',
            'allow_cash_refund' => 'boolean',

            'is_active' => 'boolean',
            'action' => 'nullable|in:save,save_and_new',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['tracked_as_inventory'] = $validated['tracked_as_inventory'] ?? false;
        $validated['low_stock_alerts'] = $validated['low_stock_alerts'] ?? true;
        $validated['batch_expiry_tracking'] = $validated['batch_expiry_tracking'] ?? false;
        $validated['serial_tracking'] = $validated['serial_tracking'] ?? false;

        if (isset($validated['item_category_id'])) {
            $validated['category_id'] = $validated['item_category_id'];
        }
        unset($validated['item_category_id'], $validated['action']);

        $isReturnable = ($validated['is_returnable'] ?? false) && $validated['tracked_as_inventory'];
        unset($validated['is_returnable']);

        $returnableData = null;
        if ($isReturnable) {
            $returnableData = [
                'company_id' => $companyId,
                'container_type' => $validated['container_type'] ?? 'bottle',
                'deposit_value' => $validated['deposit_value'] ?? 0,
                'deposit_tax_handling' => $validated['deposit_tax_handling'] ?? 'excluded',
                'return_window_days' => $validated['return_window_days'] ?? 30,
                'linked_empty_item_id' => $validated['linked_empty_item_id'] ?? null,
                'linked_filled_item_id' => $validated['linked_filled_item_id'] ?? null,
                'required_return' => $validated['required_return'] ?? 'one_to_one',
                'container_stock_account_id' => $validated['container_stock_account_id'] ?? null,
                'container_stock_tracking' => $validated['container_stock_tracking'] ?? true,
                'allow_cash_refund' => $validated['allow_cash_refund'] ?? false,
            ];
        }

        foreach (['container_type', 'deposit_value', 'deposit_tax_handling', 'return_window_days',
                  'linked_empty_item_id', 'linked_filled_item_id', 'required_return',
                  'container_stock_account_id', 'container_stock_tracking', 'allow_cash_refund'] as $k) {
            unset($validated[$k]);
        }

        $action = $request->input('action', 'save');

        $product = DB::transaction(function () use ($validated, $returnableData, $companyId) {
            $product = Product::create($validated);

            if ($returnableData) {
                $returnableData['item_id'] = $product->id;
                ItemReturnable::create($returnableData);
            }

            return $product;
        });

        if ($action === 'save_and_new') {
            return redirect()->route('accounting.inventory.items.create')
                ->with('success', 'Item created successfully. Add another.');
        }

        return redirect()->route('accounting.inventory.items.show', $product)
            ->with('success', 'Item created successfully.');
    }

    public function itemsShow(Product $product)
    {
        $companyId = session('current_company_id');
        abort_unless($product->company_id === $companyId, 403);

        $product->load('itemCategory', 'incomeAccount', 'expenseAccount');

        $stockOnHand = $product->stock_qty ?? 0;
        $reorderPoint = $product->effective_reorder_point ?? 0;
        $salesPrice = (float) ($product->sales_price ?? 0);
        $purchasePrice = (float) ($product->purchase_price ?? 0);
        $margin = $salesPrice - $purchasePrice;
        $marginPct = $salesPrice > 0 ? round(($margin / $salesPrice) * 100) : 0;

        $isOut = $product->tracked_as_inventory && $stockOnHand <= 0;
        $isLow = $product->tracked_as_inventory && $reorderPoint > 0 && $stockOnHand > 0 && $stockOnHand <= $reorderPoint;

        $recentMovements = DB::table('inventory_cost_layers')
            ->where('product_id', $product->id)
            ->where('company_id', $companyId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(10)
            ->get(['id', 'quantity_remaining', 'unit_cost', 'source_type', 'source_id', 'date']);

        $cs = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        return view('accounting.inventory.items-show', compact(
            'product', 'stockOnHand', 'reorderPoint', 'salesPrice', 'purchasePrice',
            'margin', 'marginPct', 'isOut', 'isLow', 'recentMovements', 'cs'
        ));
    }

    public function itemsEdit(Product $product)
    {
        $companyId = session('current_company_id');
        abort_unless($product->company_id === $companyId, 403);

        $categories = ItemCategory::forCompany($companyId)->orderBy('name')->get(['id', 'name']);
        $incomeAccounts = Account::forCompany($companyId)->whereIn('sub_type', ['income', 'revenue'])->orderBy('code')->get(['id', 'code', 'name']);
        $expenseAccounts = Account::forCompany($companyId)->where('sub_type', 'cost_of_goods_sold')->orderBy('code')->get(['id', 'code', 'name']);
        $inventoryAccounts = Account::forCompany($companyId)->where('sub_type', 'current_asset')->orderBy('code')->get(['id', 'code', 'name']);
        $suppliers = Vendor::forCompany($companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $warehouses = \App\Models\Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $product->load('returnable');

        $hasStockMovements = $product->costLayers()->exists();
        $hasTransactions = $product->invoiceLines()->exists() || $product->billLines()->exists();

        return view('accounting.inventory.items-edit', compact(
            'product', 'categories', 'incomeAccounts', 'expenseAccounts', 'inventoryAccounts',
            'suppliers', 'warehouses', 'hasStockMovements', 'hasTransactions'
        ));
    }

    public function itemsUpdate(Request $request, Product $product)
    {
        $companyId = session('current_company_id');
        abort_unless($product->company_id === $companyId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)->where('company_id', $companyId)],
            'barcode' => 'nullable|string|max:50',
            'type' => 'required|in:goods,service,bundle',
            'brand' => 'nullable|string|max:100',
            'item_category_id' => 'nullable|exists:item_categories,id',
            'unit_of_measure' => 'nullable|string|max:20',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string|max:5000',

            'sales_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'income_account_id' => 'nullable|exists:accounts,id',
            'expense_account_id' => 'nullable|exists:accounts,id',
            'inventory_asset_account_id' => 'nullable|exists:accounts,id',
            'price_list' => 'nullable|string|max:50',

            'tracked_as_inventory' => 'boolean',
            'max_stock' => 'nullable|integer|min:0',
            'reorder_qty' => 'nullable|integer|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'default_supplier_id' => 'nullable|exists:vendors,id',
            'costing_method' => 'nullable|in:weighted_average,fifo',
            'low_stock_alerts' => 'boolean',
            'batch_expiry_tracking' => 'boolean',
            'serial_tracking' => 'boolean',

            'is_returnable' => 'boolean',
            'container_type' => 'nullable|in:bottle,crate,keg,cylinder',
            'deposit_value' => 'nullable|numeric|min:0',
            'deposit_tax_handling' => 'nullable|in:excluded,taxed',
            'return_window_days' => 'nullable|integer|min:1',
            'linked_empty_item_id' => 'nullable|exists:products,id',
            'linked_filled_item_id' => 'nullable|exists:products,id',
            'required_return' => 'nullable|in:one_to_one,free',
            'container_stock_account_id' => 'nullable|exists:accounts,id',
            'container_stock_tracking' => 'boolean',
            'allow_cash_refund' => 'boolean',

            'is_active' => 'boolean',
        ]);

        $validated['tracked_as_inventory'] = $validated['tracked_as_inventory'] ?? false;
        $validated['low_stock_alerts'] = $validated['low_stock_alerts'] ?? true;
        $validated['batch_expiry_tracking'] = $validated['batch_expiry_tracking'] ?? false;
        $validated['serial_tracking'] = $validated['serial_tracking'] ?? false;

        if (isset($validated['item_category_id'])) {
            $validated['category_id'] = $validated['item_category_id'];
        }
        unset($validated['item_category_id']);

        $isReturnable = ($validated['is_returnable'] ?? false) && $validated['tracked_as_inventory'];
        unset($validated['is_returnable']);

        $returnableData = null;
        if ($isReturnable) {
            $returnableData = [
                'container_type' => $validated['container_type'] ?? 'bottle',
                'deposit_value' => $validated['deposit_value'] ?? 0,
                'deposit_tax_handling' => $validated['deposit_tax_handling'] ?? 'excluded',
                'return_window_days' => $validated['return_window_days'] ?? 30,
                'linked_empty_item_id' => $validated['linked_empty_item_id'] ?? null,
                'linked_filled_item_id' => $validated['linked_filled_item_id'] ?? null,
                'required_return' => $validated['required_return'] ?? 'one_to_one',
                'container_stock_account_id' => $validated['container_stock_account_id'] ?? null,
                'container_stock_tracking' => $validated['container_stock_tracking'] ?? true,
                'allow_cash_refund' => $validated['allow_cash_refund'] ?? false,
            ];
        }

        foreach (['container_type', 'deposit_value', 'deposit_tax_handling', 'return_window_days',
                  'linked_empty_item_id', 'linked_filled_item_id', 'required_return',
                  'container_stock_account_id', 'container_stock_tracking', 'allow_cash_refund'] as $k) {
            unset($validated[$k]);
        }

        DB::transaction(function () use ($product, $validated, $returnableData, $companyId, $isReturnable) {
            $product->update($validated);

            if ($isReturnable && $returnableData) {
                $returnableData['company_id'] = $companyId;
                $product->returnable()->updateOrCreate(
                    ['item_id' => $product->id],
                    $returnableData
                );
            } elseif (!$isReturnable && $product->relationLoaded('returnable') && $product->returnable) {
                $product->returnable()->delete();
            }
        });

        return redirect()->route('accounting.inventory.items.show', $product)
            ->with('success', 'Item updated successfully.');
    }

}
