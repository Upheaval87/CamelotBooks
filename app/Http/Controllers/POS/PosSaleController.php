<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\ItemUomConversion;
use App\Models\MobileMoneyProvider;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\Product;
use App\Services\Accounting\InventoryService;
use App\Services\Inventory\UnitOfMeasureConversionService;
use App\Services\POS\PosSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosSaleController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $q = $request->input('q');
        $method = $request->input('method');
        $status = $request->input('status');

        $query = PosSale::where('company_id', $companyId)
            ->with(['customer', 'terminal', 'payments.paymentMethod'])
            ->latest();

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('sale_number', 'like', "%{$q}%")
                  ->orWhereHas('customer', fn ($cw) => $cw->where('name', 'like', "%{$q}%"));
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($method) {
            $query->whereHas('payments', fn ($pw) => $pw->where('payment_method_id', $method));
        }

        $sales = $query->paginate(25)->withQueryString();

        $stats = PosSale::where('company_id', $companyId)
            ->selectRaw('status, count(*) as count, sum(total) as total')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalSales = PosSale::where('company_id', $companyId)->sum('total');
        $totalCount = PosSale::where('company_id', $companyId)->count();
        $todaySales = PosSale::where('company_id', $companyId)->whereDate('created_at', today())->sum('total');
        $todayCount = PosSale::where('company_id', $companyId)->whereDate('created_at', today())->count();
        $avgSale = $totalCount > 0 ? $totalSales / $totalCount : 0;

        $paymentMethods = \App\Models\PosPaymentMethod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.receipts.index', compact(
            'sales', 'stats', 'totalSales', 'totalCount', 'todaySales', 'todayCount',
            'avgSale', 'paymentMethods', 'q', 'method', 'status'
        ));
    }

    public function checkout()
    {
        $companyId = session('current_company_id');
        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sales_price', 'tax_rate', 'is_taxable', 'tracked_as_inventory']);

        // Bulk stock query — single query scoped to terminal's branch
        $branchId = session('pos_terminal_branch_id');
        $stockByProduct = [];
        if ($branchId) {
            $stockRows = \App\Models\InventoryStock::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $products->pluck('id')->filter(fn ($id) => $id !== null))
                ->select('product_id', DB::raw('SUM(quantity_on_hand) as qty'))
                ->groupBy('product_id')
                ->get();
            foreach ($stockRows as $row) {
                $stockByProduct[$row->product_id] = (float) $row->qty;
            }
        } else {
            // Fallback: no branch on terminal, show total across all branches
            $stockRows = \App\Models\InventoryStock::where('company_id', $companyId)
                ->whereIn('product_id', $products->pluck('id')->filter(fn ($id) => $id !== null))
                ->select('product_id', DB::raw('SUM(quantity_on_hand) as qty'))
                ->groupBy('product_id')
                ->get();
            foreach ($stockRows as $row) {
                $stockByProduct[$row->product_id] = (float) $row->qty;
            }
        }

        $products->each(function ($product) use ($stockByProduct) {
            $product->current_stock = $product->tracked_as_inventory
                ? ($stockByProduct[$product->id] ?? 0)
                : null;
        });

        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'clearing_account_id', 'requires_reference']);
        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name']);

        $mobileProviders = MobileMoneyProvider::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Preload UOM conversions keyed by product_id
        $productIds = $products->pluck('id')->filter()->values();
        $uomConversions = [];
        if ($productIds->isNotEmpty()) {
            $uomRows = ItemUomConversion::where('company_id', $companyId)
                ->whereIn('product_id', $productIds)
                ->where('is_active', true)
                ->orderBy('product_id')
                ->orderBy('is_base', 'desc')
                ->orderBy('conversion_factor', 'asc')
                ->get(['product_id', 'uom_name', 'conversion_factor', 'sales_price', 'is_base']);
            foreach ($uomRows as $row) {
                $uomConversions[$row->product_id][] = [
                    'uom_name' => $row->uom_name,
                    'conversion_factor' => (float) $row->conversion_factor,
                    'sales_price' => (float) $row->sales_price,
                    'is_base' => (bool) $row->is_base,
                ];
            }
        }

        return view('pos.sales.checkout', compact('products', 'paymentMethods', 'customers', 'bankAccounts', 'mobileProviders', 'uomConversions'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
            'cashier_session_id' => 'nullable|exists:pos_cashier_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'reference' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.discount_type' => 'nullable|string',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.transaction_uom' => 'nullable|string|max:50',
            'lines.*.transaction_qty' => 'nullable|numeric|min:0.01',
            'lines.*.conversion_factor' => 'nullable|numeric|min:0.01',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.cash_tendered' => 'nullable|numeric|min:0',
            'payments.*.change_given' => 'nullable|numeric|min:0',
            'payments.*.reference_number' => 'nullable|string|max:255',
            'payments.*.processor_name' => 'nullable|string|max:255',
            'payments.*.account_name' => 'nullable|string|max:255',
            'payments.*.institution' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = $companyId;
        $validated['branch_id'] = $validated['branch_id'] ?? session('pos_terminal_branch_id');

        try {
            $sale = app(PosSaleService::class)->checkout($validated, $userId);

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => $sale->total,
                'message' => "Sale {$sale->sale_number} completed.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(int $id)
    {
        $companyId = session('current_company_id');

        $sale = \App\Models\PosSale::where('company_id', $companyId)
            ->with(['lines.product', 'payments.paymentMethod', 'terminal', 'customer'])
            ->findOrFail($id);

        return view('pos.sales.receipt', compact('sale'));
    }

    public function linesJson(int $id)
    {
        $companyId = session('current_company_id');

        $sale = \App\Models\PosSale::where('company_id', $companyId)
            ->with(['lines.product'])
            ->findOrFail($id);

        return response()->json([
            'lines' => $sale->lines->map(fn ($line) => [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'product_name' => $line->product?->name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_rate' => $line->tax_rate,
                'line_total' => $line->line_total,
                'cost_of_goods' => $line->cost_of_goods,
            ]),
        ]);
    }

    public function syncOffline(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
            'cashier_session_id' => 'nullable|exists:pos_cashier_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'reference' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.discount_type' => 'nullable|string',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.transaction_uom' => 'nullable|string|max:50',
            'lines.*.transaction_qty' => 'nullable|numeric|min:0.01',
            'lines.*.conversion_factor' => 'nullable|numeric|min:0.01',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.cash_tendered' => 'nullable|numeric|min:0',
            'payments.*.change_given' => 'nullable|numeric|min:0',
            'payments.*.reference_number' => 'nullable|string|max:255',
            'payments.*.processor_name' => 'nullable|string|max:255',
            'payments.*.account_name' => 'nullable|string|max:255',
            'payments.*.institution' => 'nullable|string|max:255',
            '_offlineId' => 'nullable|string|max:255',
        ]);

        $offlineId = $validated['_offlineId'] ?? null;
        unset($validated['_offlineId']);

        if ($offlineId) {
            $existing = PosSale::where('company_id', $companyId)
                ->where('offline_transaction_id', $offlineId)
                ->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'sale_id' => $existing->id,
                    'sale_number' => $existing->sale_number,
                    'total' => $existing->total,
                    'message' => "Sale {$existing->sale_number} already synced.",
                ]);
            }
        }

        $validated['company_id'] = $companyId;
        $validated['branch_id'] = $validated['branch_id'] ?? session('pos_terminal_branch_id');

        try {
            $sale = app(PosSaleService::class)->checkout($validated, $userId);

            if ($offlineId) {
                $sale->update([
                    'synced_from_offline' => true,
                    'offline_transaction_id' => $offlineId,
                ]);
            }

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => $sale->total,
                'message' => "Sale {$sale->sale_number} synced from offline.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
