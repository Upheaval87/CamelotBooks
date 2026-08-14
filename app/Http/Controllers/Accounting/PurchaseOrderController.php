<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct(protected BillService $billService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = PurchaseOrder::where('company_id', $companyId)
            ->with(['vendor', 'lines']);

        if ($request->filled('status')) {
            $query->forStatus($request->status);
        }

        $orders = $query->orderByDesc('date')->paginate(15)->withQueryString();

        $stats = PurchaseOrder::where('company_id', $companyId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('accounting.purchase-orders.index', compact('orders', 'stats'));
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedVendorId = $request->input('vendor_id');

        $requisition = null;
        if ($request->filled('requisition_id')) {
            $requisition = PurchaseRequisition::where('company_id', $companyId)
                ->where('status', 'approved')
                ->with('lines.product')
                ->find($request->requisition_id);
        }

        return view('accounting.purchase-orders.create', compact('vendors', 'products', 'accounts', 'costCenters', 'branches', 'requisition', 'itemCategories', 'selectedVendorId'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'requisition_id' => ['nullable', 'integer', 'exists:purchase_requisitions,id'],
            'date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
        ]);

        DB::beginTransaction();

        try {
            $poNumber = $this->generatePoNumber($companyId);

            $order = PurchaseOrder::create([
                'company_id' => $companyId,
                'branch_id' => $validated['branch_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'vendor_id' => $validated['vendor_id'],
                'requisition_id' => $validated['requisition_id'] ?? null,
                'po_number' => $poNumber,
                'date' => $validated['date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'memo' => $validated['memo'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['lines'] as $lineData) {
                $amount = round($lineData['quantity'] * $lineData['unit_price'], 2);

                PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $lineData['product_id'] ?? null,
                    'description' => $lineData['description'],
                    'quantity' => $lineData['quantity'],
                    'unit_price' => $lineData['unit_price'],
                    'amount' => $amount,
                    'expense_account_id' => $lineData['expense_account_id'],
                    'cost_center_id' => $lineData['cost_center_id'] ?? null,
                ]);
            }

            if (!empty($validated['requisition_id'])) {
                $requisition = PurchaseRequisition::forCompany($companyId)
                    ->where('status', PurchaseRequisition::STATUS_APPROVED)
                    ->find($validated['requisition_id']);

                if ($requisition) {
                    $requisition->update([
                        'status' => PurchaseRequisition::STATUS_CONVERTED,
                        'converted_to_po_id' => $order->id,
                        'converted_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('accounting.purchase-orders.show', $order)
                ->with('success', 'Purchase order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseOrder->company_id == $companyId, 403);

        $purchaseOrder->load(['vendor', 'lines.product', 'lines.costCenter', 'requisition', 'grns', 'journalEntry']);

        $totalAmount = $purchaseOrder->lines->sum('amount');

        return view('accounting.purchase-orders.show', ['order' => $purchaseOrder, 'totalAmount' => $totalAmount]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseOrder->company_id == $companyId, 403);

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            abort(403, 'Only draft purchase orders can be edited.');
        }

        $purchaseOrder->load('lines');

        $companyId = session('current_company_id');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.purchase-orders.edit', [
            'order' => $purchaseOrder,
            'vendors' => $vendors,
            'products' => $products,
            'accounts' => $accounts,
            'costCenters' => $costCenters,
            'branches' => $branches,
            'itemCategories' => $itemCategories,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseOrder->company_id == $companyId, 403);

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            abort(403, 'Only draft purchase orders can be updated.');
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
        ]);

        DB::beginTransaction();

        try {
            $purchaseOrder->update([
                'vendor_id' => $validated['vendor_id'],
                'date' => $validated['date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'memo' => $validated['memo'] ?? null,
            ]);

            $purchaseOrder->lines()->delete();

            foreach ($validated['lines'] as $lineData) {
                $amount = round($lineData['quantity'] * $lineData['unit_price'], 2);

                PurchaseOrderLine::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $lineData['product_id'] ?? null,
                    'description' => $lineData['description'],
                    'quantity' => $lineData['quantity'],
                    'unit_price' => $lineData['unit_price'],
                    'amount' => $amount,
                    'expense_account_id' => $lineData['expense_account_id'],
                    'cost_center_id' => $lineData['cost_center_id'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function confirm(PurchaseOrder $purchaseOrder)
    {
        $this->requirePermission('purchase-orders.confirm');
        $companyId = session('current_company_id');
        abort_unless($purchaseOrder->company_id == $companyId, 403);

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->back()->withErrors(['error' => 'Only draft purchase orders can be confirmed.']);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_SENT]);

        return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order confirmed and sent to vendor.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->requirePermission('purchase-orders.cancel');
        $companyId = session('current_company_id');
        abort_unless($purchaseOrder->company_id == $companyId, 403);

        if (!in_array($purchaseOrder->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT])) {
            return redirect()->back()->withErrors(['error' => 'Only draft or sent purchase orders can be cancelled.']);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order cancelled.');
    }

    /**
     * Convert a received purchase order into a draft vendor bill.
     *
     * Bills only the received-but-unbilled quantity of each line
     * (quantity_received - quantity_billed) using the existing BillService,
     * so the draft bill can be reviewed before posting.
     */
    public function convert(PurchaseOrder $purchaseOrder)
    {
        $this->requirePermission('bills.create');
        $companyId = session('current_company_id');
        abort_unless($purchaseOrder->company_id == $companyId, 403);

        if (!in_array($purchaseOrder->status, [
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            PurchaseOrder::STATUS_FULLY_RECEIVED,
        ])) {
            return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
                ->withErrors(['error' => 'Only sent or received purchase orders can be converted to a bill.']);
        }

        $purchaseOrder->load(['vendor', 'lines']);

        $lines = [];

        foreach ($purchaseOrder->lines as $line) {
            $billable = round((float) $line->quantity_received - (float) $line->quantity_billed, 2);

            if ($billable <= 0) {
                continue;
            }

            $lines[] = [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $billable,
                'unit_price' => $line->unit_price,
                'expense_account_id' => $line->expense_account_id,
                'cost_center_id' => $line->cost_center_id,
                'purchase_order_line_id' => $line->id,
            ];
        }

        if (empty($lines)) {
            return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
                ->withErrors(['error' => 'There are no received quantities left to bill on this purchase order.']);
        }

        $days = max(1, (int) ($purchaseOrder->vendor->payment_terms_days ?? 30));

        try {
            $bill = $this->billService->create([
                'company_id' => $companyId,
                'vendor_id' => $purchaseOrder->vendor_id,
                'purchase_order_id' => $purchaseOrder->id,
                'branch_id' => $purchaseOrder->branch_id,
                'cost_center_id' => $purchaseOrder->cost_center_id,
                'bill_date' => now()->toDateString(),
                'due_date' => now()->addDays($days)->toDateString(),
                'po_number' => $purchaseOrder->po_number,
                'reference' => 'From ' . $purchaseOrder->po_number,
                'memo' => 'Bill generated from purchase order ' . $purchaseOrder->po_number,
                'currency' => 'USD',
                'lines' => $lines,
            ], auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Draft bill created from purchase order. Review it before posting.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function generatePoNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'PO-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = PurchaseOrder::where('company_id', $companyId)
            ->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('po_number')
            ->first();

        if ($last) {
            $lastSequence = (int) substr($last->po_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }
}
