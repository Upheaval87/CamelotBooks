<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\InventoryAdjustment;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\StockCount;
use App\Models\AssemblyBuild;
use App\Models\ItemUomConversion;
use App\Models\LandedCostVoucher;
use App\Services\Accounting\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventorySetupController extends Controller
{
    public function categories()
    {
        $companyId = session('current_company_id');

        $categories = ItemCategory::forCompany($companyId)
            ->with(['products' => fn ($q) => $q->select('id', 'category_id')])
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $selected = $categories->first();

        return view('accounting.invsetup.categories', compact('categories', 'selected'));
    }

    public function assemblies()
    {
        $companyId = session('current_company_id');

        $assemblies = Product::forCompany($companyId)
            ->where('type', 'goods')
            ->where('tracked_as_inventory', true)
            ->with('itemCategory')
            ->orderBy('name')
            ->paginate(15);

        $assemblyHistory = AssemblyBuild::forCompany($companyId)
            ->with('product')
            ->latest()
            ->take(20)
            ->get();

        return view('accounting.invsetup.assemblies', compact('assemblies', 'assemblyHistory'));
    }

    public function transfers()
    {
        $companyId = session('current_company_id');

        $transfers = InventoryTransfer::forCompany($companyId)
            ->latest()
            ->paginate(15);

        $statusCounts = InventoryTransfer::forCompany($companyId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('accounting.invsetup.transfers', compact('transfers', 'statusCounts'));
    }

    public function transferCreate()
    {
        $companyId = session('current_company_id');

        $products = Product::forCompany($companyId)
            ->where('tracked_as_inventory', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $branches = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        // Build per-product, per-branch on-hand from inventory_stock
        $stockRows = InventoryStock::forCompany($companyId)
            ->where('quantity_on_hand', '>', 0)
            ->get(['product_id', 'branch_id', 'quantity_on_hand']);

        $onHand = [];
        foreach ($stockRows as $row) {
            $onHand[$row->product_id][$row->branch_id] = (float) $row->quantity_on_hand;
        }

        // Weighted-average unit cost per product from cost layers
        $costRows = InventoryCostLayer::forCompany($companyId)
            ->available()
            ->selectRaw('product_id, SUM(quantity_remaining * unit_cost) as total_cost, SUM(quantity_remaining) as total_qty')
            ->groupBy('product_id')
            ->get();

        $unitCosts = [];
        foreach ($costRows as $row) {
            $unitCosts[$row->product_id] = $row->total_qty > 0 ? round($row->total_cost / $row->total_qty, 4) : 0;
        }

        // Merge into products array for JSON payload
        $productsData = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'on_hand' => $onHand[$p->id] ?? [],
            'unit_cost' => $unitCosts[$p->id] ?? 0,
        ]);

        $branchesData = $branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]);

        return view('accounting.invsetup.transfer-create', compact(
            'products', 'branches', 'productsData', 'branchesData', 'cs'
        ));
    }

    public function transferStore(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'product_id'    => 'required|integer|exists:products,id',
            'quantity'      => 'required|numeric|min:0.01',
            'from_branch_id' => 'required|integer|exists:branches,id',
            'to_branch_id'   => 'required|integer|exists:branches,id',
            'date'          => 'required|date',
            'memo'          => 'nullable|string|max:5000',
        ]);

        if ($validated['from_branch_id'] == $validated['to_branch_id']) {
            return back()->withErrors(['to_branch_id' => __('Source and destination branches must be different.')])->withInput();
        }

        $service = app(InventoryService::class);
        $result = $service->transferStock(
            $companyId,
            $validated['product_id'],
            $validated['from_branch_id'],
            $validated['to_branch_id'],
            $validated['quantity'],
            $validated['memo'] ?? null,
            $userId,
            $validated['date']
        );

        return redirect()->route('accounting.invsetup.transfers')
            ->with('success', __('Transfer completed. Ref: :ref', ['ref' => $result['transfer']->transfer_number]));
    }

    public function adjustments()
    {
        $companyId = session('current_company_id');

        $adjustments = InventoryAdjustment::forCompany($companyId)
            ->with('product')
            ->latest()
            ->paginate(15);

        $typeCounts = InventoryAdjustment::forCompany($companyId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $transfers = InventoryTransfer::forCompany($companyId)
            ->latest()
            ->paginate(15);

        $statusCounts = InventoryTransfer::forCompany($companyId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('accounting.invsetup.transfers', compact('adjustments', 'typeCounts', 'transfers', 'statusCounts'));
    }

    public function stockCount()
    {
        $companyId = session('current_company_id');

        $counts = StockCount::forCompany($companyId)
            ->withCount('lines')
            ->latest()
            ->paginate(15);

        $warehouses = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('accounting.invsetup.stock-count', compact('counts', 'warehouses'));
    }

    public function uom()
    {
        $companyId = session('current_company_id');

        $uomConversions = ItemUomConversion::forCompany($companyId)
            ->with('product')
            ->latest()
            ->paginate(20);

        $landedCosts = LandedCostVoucher::forCompany($companyId)
            ->latest()
            ->paginate(10);

        return view('accounting.invsetup.uom-landed', compact('uomConversions', 'landedCosts'));
    }

    public function landed()
    {
        $companyId = session('current_company_id');

        $uomConversions = ItemUomConversion::forCompany($companyId)
            ->with('product')
            ->latest()
            ->paginate(20);

        $landedCosts = LandedCostVoucher::forCompany($companyId)
            ->latest()
            ->paginate(10);

        return view('accounting.invsetup.uom-landed', compact('uomConversions', 'landedCosts'));
    }

    public function valuation()
    {
        $companyId = session('current_company_id');

        $valuationTotal = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->sum(DB::raw('quantity_remaining * unit_cost'));

        $itemsValued = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->distinct('product_id')
            ->count('product_id');

        $warehouseCount = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $trackedProducts = Product::forCompany($companyId)
            ->where('tracked_as_inventory', true)
            ->with('itemCategory')
            ->orderBy('name')
            ->get();

        $lowStockItems = Product::forCompany($companyId)
            ->where('tracked_as_inventory', true)
            ->where('reorder_point', '>', 0)
            ->with('itemCategory')
            ->get()
            ->filter(fn ($p) => $p->stock_qty <= $p->reorder_point)
            ->sortBy('name')
            ->values();

        $method = 'Weighted Average';

        return view('accounting.invsetup.valuation-lowstock', compact(
            'valuationTotal', 'itemsValued', 'warehouseCount', 'method', 'trackedProducts', 'lowStockItems'
        ));
    }

    public function lowStock()
    {
        $companyId = session('current_company_id');

        $valuationTotal = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->sum(DB::raw('quantity_remaining * unit_cost'));

        $itemsValued = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->distinct('product_id')
            ->count('product_id');

        $warehouseCount = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $trackedProducts = Product::forCompany($companyId)
            ->where('tracked_as_inventory', true)
            ->with('itemCategory')
            ->orderBy('name')
            ->get();

        $lowStockItems = Product::forCompany($companyId)
            ->where('tracked_as_inventory', true)
            ->where('reorder_point', '>', 0)
            ->with('itemCategory')
            ->get()
            ->filter(fn ($p) => $p->stock_qty <= $p->reorder_point)
            ->sortBy('name')
            ->values();

        $method = 'Weighted Average';

        return view('accounting.invsetup.valuation-lowstock', compact(
            'valuationTotal', 'itemsValued', 'warehouseCount', 'method', 'trackedProducts', 'lowStockItems'
        ));
    }
}
