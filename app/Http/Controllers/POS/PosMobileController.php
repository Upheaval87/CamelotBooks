<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\ItemCategory;
use App\Models\MobileMoneyProvider;
use App\Models\PosPaymentMethod;
use App\Models\PosReturnable;
use App\Models\PosSale;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Services\POS\PosReturnableService;
use App\Services\POS\PosSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosMobileController extends Controller
{
    /**
     * §6 — Mobile Home
     * Greeting, summary strip, quick actions, recent activity, bottom nav.
     */
    public function home()
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();
        $terminalId = session('pos_terminal_id');
        $branchId = session('pos_terminal_branch_id');

        $today = now()->toDateString();

        $todaySales = PosSale::where('company_id', $companyId)
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'voided');
        if ($branchId) {
            $todaySales->where('branch_id', $branchId);
        }
        $todayCount = $todaySales->count();
        $todayRevenue = (float) $todaySales->sum('total');

        $recentSales = PosSale::where('company_id', $companyId)
            ->with(['customer', 'payments.paymentMethod'])
            ->where('status', '!=', 'voided')
            ->latest()
            ->limit(10)
            ->get();

        $terminal = $terminalId ? PosTerminal::find($terminalId) : null;
        $cashierName = auth()->user()->name ?? 'Cashier';

        return view('pos.mobile.home', compact(
            'todayCount', 'todayRevenue', 'recentSales', 'terminal', 'cashierName'
        ));
    }

    /**
     * §7 — Mobile Sell
     * Product grid, search+scan, category tabs, cart bar.
     * Reuses the same data loading pattern as PosSaleController@checkout.
     */
    public function sell()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'barcode', 'sales_price', 'tax_rate', 'is_taxable', 'tracked_as_inventory', 'category_id']);

        $branchId = session('pos_terminal_branch_id');
        $stockByProduct = [];
        if ($branchId) {
            $stockRows = \App\Models\InventoryStock::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $products->pluck('id')->filter())
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

        $categories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'clearing_account_id', 'requires_reference']);

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $mobileProviders = MobileMoneyProvider::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $walkInCustomer = Customer::where('company_id', $companyId)
            ->where('name', 'Walk-in Customer')
            ->first(['id', 'name']);

        return view('pos.mobile.sell', compact(
            'products', 'categories', 'paymentMethods', 'customers',
            'bankAccounts', 'mobileProviders', 'walkInCustomer'
        ));
    }

    /**
     * §8 — Mobile Checkout
     * 2-page swipe: Cart → Payment. POSTs to the existing pos.sales.store.
     * This is a GET view only; the actual sale is created via AJAX to pos.sales.store.
     */
    public function checkout()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sales_price', 'tax_rate', 'is_taxable', 'tracked_as_inventory', 'category_id']);

        $branchId = session('pos_terminal_branch_id');
        $stockByProduct = [];
        if ($branchId) {
            $stockRows = \App\Models\InventoryStock::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $products->pluck('id')->filter())
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

        $categories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'clearing_account_id', 'requires_reference']);

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $mobileProviders = MobileMoneyProvider::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $walkInCustomer = Customer::where('company_id', $companyId)
            ->where('name', 'Walk-in Customer')
            ->first(['id', 'name']);

        return view('pos.mobile.checkout', compact(
            'products', 'categories', 'paymentMethods', 'customers',
            'bankAccounts', 'mobileProviders', 'walkInCustomer'
        ));
    }

    /**
     * §9 — Mobile Receipt
     * Invoice-branded receipt for a completed sale.
     */
    public function receipt(int $id)
    {
        $companyId = session('current_company_id');

        $sale = PosSale::where('company_id', $companyId)
            ->with(['lines.product', 'payments.paymentMethod', 'terminal', 'customer'])
            ->findOrFail($id);

        $company = \App\Models\Company::find($companyId);

        return view('pos.mobile.receipt', compact('sale', 'company'));
    }

    /**
     * API: products JSON for mobile search (lightweight, no view).
     */
    public function productsJson(Request $request)
    {
        $companyId = session('current_company_id');
        $q = $request->input('q', '');

        $query = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%")
                  ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        $products = $query->limit(50)->get(['id', 'name', 'sku', 'barcode', 'sales_price', 'tax_rate', 'is_taxable', 'tracked_as_inventory']);

        $branchId = session('pos_terminal_branch_id');
        $ids = $products->pluck('id')->filter();
        $stockByProduct = [];
        if ($branchId && $ids->isNotEmpty()) {
            $stockRows = \App\Models\InventoryStock::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $ids)
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

        return response()->json($products);
    }

    /**
     * §10 — Mobile Receipts / History
     * Branch + Till selectors, payment filter chips, day-grouped list.
     */
    public function receipts(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();
        $user = auth()->user();
        $branchId = session('pos_terminal_branch_id');

        // Branch selector (§10.1 — scoping)
        // "Head office" = user has no branch restriction on their assignment
        $assignment = $user->companyAssignments()
            ->where('company_id', $companyId)
            ->first();
        $canSeeAllBranches = ! $assignment || empty($assignment->branch_ids);
        if ($canSeeAllBranches) {
            $branches = \App\Models\Branch::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $branches = \App\Models\Branch::where('company_id', $companyId)
                ->where('id', $branchId)
                ->where('is_active', true)
                ->get(['id', 'name']);
        }

        // Till/Register selector
        $terminals = PosTerminal::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'identifier', 'name']);

        // Filters
        $filterBranch = $request->input('branch_id', $branchId ?? '');
        $filterTerminal = $request->input('terminal_id', '');
        $filterMethod = $request->input('method', '');

        // Enforce branch scoping server-side (§10.1)
        if (!$canSeeAllBranches && $filterBranch && !$branches->contains('id', $filterBranch)) {
            $filterBranch = $branchId ?? '';
        }

        $query = PosSale::where('company_id', $companyId)
            ->with(['customer', 'payments.paymentMethod'])
            ->where('status', '!=', 'voided')
            ->latest();

        if ($filterBranch) {
            $query->where('branch_id', $filterBranch);
        }
        if ($filterTerminal) {
            $query->where('terminal_id', $filterTerminal);
        }
        if ($filterMethod && $filterMethod !== 'all') {
            $methodType = match ($filterMethod) {
                'cash' => 'cash',
                'card' => 'card',
                'mobile' => 'mobile_money',
                'credit' => 'credit',
                default => null,
            };
            if ($methodType) {
                $query->whereHas('payments', function ($q) use ($methodType) {
                    $q->whereHas('paymentMethod', fn($pm) => $pm->where('type', $methodType));
                });
            }
        }

        $sales = $query->limit(100)->get();

        // Group by date
        $grouped = $sales->groupBy(fn($sale) => $sale->created_at->format('Y-m-d'))
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'label' => $items->first()->created_at->isToday() ? 'Today' :
                               ($items->first()->created_at->isYesterday() ? 'Yesterday' : $items->first()->created_at->format('d M')),
                    'items' => $items,
                ];
            })
            ->values();

        // Summary stats
        $todaySales = PosSale::where('company_id', $companyId)
            ->whereDate('created_at', now()->toDateString())
            ->where('status', '!=', 'voided');
        if ($filterBranch) {
            $todaySales->where('branch_id', $filterBranch);
        }
        $todayCount = $todaySales->count();
        $todayRevenue = (float) $todaySales->sum('total');

        return view('pos.mobile.receipts-list', compact(
            'branches', 'terminals', 'grouped', 'todayCount', 'todayRevenue',
            'filterBranch', 'filterTerminal', 'filterMethod'
        ));
    }

    /**
     * §11 — Mobile Register & Shift
     * Current shift status, cash count, X-report, close shift.
     */
    public function register()
    {
        $companyId = session('current_company_id');
        $terminalId = session('pos_terminal_id');
        $userId = auth()->id();

        $terminal = $terminalId ? PosTerminal::find($terminalId) : null;

        // Get today's sales for this terminal
        $today = now()->toDateString();
        $todaySales = PosSale::where('company_id', $companyId)
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'voided');
        if ($terminalId) {
            $todaySales->where('terminal_id', $terminalId);
        }

        $receiptCount = $todaySales->count();
        $totalRevenue = (float) $todaySales->sum('total');
        $cashSales = (float) (clone $todaySales)->whereHas('payments', fn($q) =>
            $q->whereHas('paymentMethod', fn($pm) => $pm->where('type', 'cash'))
        )->sum('total');
        $cardSales = (float) (clone $todaySales)->whereHas('payments', fn($q) =>
            $q->whereHas('paymentMethod', fn($pm) => $pm->where('type', 'card'))
        )->sum('total');
        $mobileSales = (float) (clone $todaySales)->whereHas('payments', fn($q) =>
            $q->whereHas('paymentMethod', fn($pm) => $pm->where('type', 'mobile_money'))
        )->sum('total');

        return view('pos.mobile.register', compact(
            'terminal', 'receiptCount', 'totalRevenue', 'cashSales', 'cardSales', 'mobileSales'
        ));
    }

    /**
     * §12 — Mobile Products
     * Product list with search, category chips, stock state. No photos.
     */
    public function products(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = session('pos_terminal_branch_id');
        $q = $request->input('q', '');
        $category = $request->input('category', '');

        $query = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%")
                  ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        if ($category) {
            $query->where('category_id', $category);
        }

        $products = $query->get(['id', 'name', 'sku', 'barcode', 'sales_price', 'tax_rate', 'is_taxable', 'tracked_as_inventory', 'category_id']);

        // Attach stock
        $stockByProduct = [];
        if ($branchId && $products->isNotEmpty()) {
            $stockRows = \App\Models\InventoryStock::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $products->pluck('id'))
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

        $categories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalCount = Product::where('company_id', $companyId)->where('is_active', true)->count();
        $lowStockCount = $products->filter(fn($p) => $p->current_stock !== null && $p->current_stock <= 10)->count();

        return view('pos.mobile.products-list', compact(
            'products', 'categories', 'totalCount', 'lowStockCount', 'q', 'category'
        ));
    }

    /**
     * §13 — Mobile Settings
     * Profile, store info, devices, preferences, account actions.
     */
    public function settings()
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();
        $user = auth()->user();
        $company = \App\Models\Company::find($companyId);
        $terminal = session('pos_terminal_id') ? PosTerminal::find(session('pos_terminal_id')) : null;

        return view('pos.mobile.settings', compact('user', 'company', 'terminal'));
    }

    // ────────────────────────────────────────────────────────
    // Phase D — Returnables Mobile (§14)
    // ────────────────────────────────────────────────────────

    /**
     * §14.1 — Mobile Intake: customer, container, qty, credit-to, confirm.
     */
    public function retIntake()
    {
        $companyId = session('current_company_id');

        // Returnable products (products with an ItemReturnable config)
        $containers = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('returnable')
            ->with('returnable')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        // Customers for the picker
        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name']);

        return view('pos.mobile.ret-intake', compact('containers', 'customers'));
    }

    /**
     * §14.1 — Store intake: delegates to PosReturnableService::intake().
     */
    public function retIntakeStore(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:customers,id',
            'bottle_count' => 'required|integer|min:1|max:9999',
            'credit_to' => 'nullable|in:store_credit,cash_refund',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = $companyId;
        $validated['branch_id'] = session('pos_terminal_branch_id');
        $validated['quantity'] = $validated['bottle_count'];

        /** @var PosReturnableService $service */
        $service = app(PosReturnableService::class);

        try {
            $returnable = $service->intake($validated, $userId);

            return redirect()->route('pos.m.ret-receipt', $returnable->id)
                ->with('success', 'Bottle Return Receipt issued.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['bottle_count' => $e->getMessage()]);
        }
    }

    /**
     * §14.2 — Mobile BRR Receipt: branded receipt doc with container/qty/value/credit table.
     */
    public function retReceipt(int $id)
    {
        $companyId = session('current_company_id');

        $returnable = PosReturnable::where('company_id', $companyId)
            ->with(['product', 'customer', 'branch', 'createdBy'])
            ->findOrFail($id);

        return view('pos.mobile.ret-receipt', compact('returnable'));
    }

    /**
     * §14.3 — Mobile Register: search + status chips + BRR cards.
     */
    public function retRegister(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $status = $request->input('status', '');
        $q = $request->input('q', '');

        $query = PosReturnable::where('company_id', $companyId)
            ->with(['product', 'customer', 'createdBy'])
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('brr_number', 'like', "%{$q}%")
                  ->orWhere('intake_number', 'like', "%{$q}%")
                  ->orWhereHas('customer', fn ($cw) => $cw->where('name', 'like', "%{$q}%"));
            });
        }

        $returnables = $query->paginate(25)->withQueryString();

        // Stats for the chips
        $baseQuery = PosReturnable::where('company_id', $companyId);
        $stats = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', PosReturnable::STATUS_PENDING)->count(),
            'partially_redeemed' => (clone $baseQuery)->where('status', PosReturnable::STATUS_PARTIALLY_REDEEMED)->count(),
            'redeemed' => (clone $baseQuery)->where('status', PosReturnable::STATUS_REDEEMED)->count(),
            'voided' => (clone $baseQuery)->where('status', PosReturnable::STATUS_VOIDED)->count(),
        ];

        return view('pos.mobile.ret-register', compact('returnables', 'stats', 'status', 'q'));
    }
}
