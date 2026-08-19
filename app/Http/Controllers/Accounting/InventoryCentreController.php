<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\InventoryAdjustment;
use App\Models\InventoryTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        $incomeAccounts = Account::forCompany($companyId)->where('sub_type', 'income')->orderBy('code')->get(['id', 'code', 'name']);
        $expenseAccounts = Account::forCompany($companyId)->where('sub_type', 'expense')->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.inventory.items-create', compact('categories', 'incomeAccounts', 'expenseAccounts'));
    }

    public function itemsStore(Request $request)
    {
        $companyId = session('current_company_id');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku,' . $companyId . ',company_id',
            'type' => 'required|in:goods,service',
            'sales_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'item_category_id' => 'nullable|exists:item_categories,id',
            'income_account_id' => 'nullable|exists:accounts,id',
            'expense_account_id' => 'nullable|exists:accounts,id',
            'tracked_as_inventory' => 'boolean',
            'description' => 'nullable|string|max:5000',
            'unit_of_measure' => 'nullable|string|max:20',
            'barcode' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['created_by'] = Auth::id();
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['tracked_as_inventory'] = $validated['tracked_as_inventory'] ?? false;

        if (isset($validated['item_category_id'])) {
            $validated['category_id'] = $validated['item_category_id'];
            unset($validated['item_category_id']);
        }

        $product = Product::create($validated);

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
        $incomeAccounts = Account::forCompany($companyId)->where('sub_type', 'income')->orderBy('code')->get(['id', 'code', 'name']);
        $expenseAccounts = Account::forCompany($companyId)->where('sub_type', 'expense')->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.inventory.items-edit', compact('product', 'categories', 'incomeAccounts', 'expenseAccounts'));
    }

    public function itemsUpdate(Request $request, Product $product)
    {
        $companyId = session('current_company_id');
        abort_unless($product->company_id === $companyId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:goods,service',
            'sales_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'item_category_id' => 'nullable|exists:item_categories,id',
            'income_account_id' => 'nullable|exists:accounts,id',
            'expense_account_id' => 'nullable|exists:accounts,id',
            'tracked_as_inventory' => 'boolean',
            'description' => 'nullable|string|max:5000',
            'unit_of_measure' => 'nullable|string|max:20',
            'barcode' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['item_category_id'])) {
            $validated['category_id'] = $validated['item_category_id'];
            unset($validated['item_category_id']);
        }

        $product->update($validated);

        return redirect()->route('accounting.inventory.items.show', $product)
            ->with('success', 'Item updated successfully.');
    }

}
