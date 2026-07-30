<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\GoodsReceivedNote;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\Accounting\GoodsReceivedNoteService;
use Illuminate\Http\Request;

class GoodsReceivedNoteController extends Controller
{
    public function __construct(protected GoodsReceivedNoteService $grnService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = GoodsReceivedNote::where('company_id', $companyId)
            ->with(['vendor', 'purchaseOrder']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notes = $query->orderByDesc('date')->paginate(15)->withQueryString();

        return view('accounting.goods-received-notes.index', compact('notes'));
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

        $purchaseOrders = PurchaseOrder::where('company_id', $companyId)
            ->whereIn('status', [PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->with(['vendor', 'lines.product'])
            ->orderByDesc('date')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedPo = null;
        if ($request->filled('purchase_order_id')) {
            $selectedPo = $purchaseOrders->firstWhere('id', $request->purchase_order_id);
        }

        return view('accounting.goods-received-notes.create', compact(
            'vendors', 'products', 'accounts', 'costCenters', 'branches', 'purchaseOrders', 'selectedPo', 'itemCategories'
        ));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'purchase_order_id' => ['nullable', 'integer'],
            'date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity_ordered' => ['nullable', 'numeric'],
            'lines.*.quantity_received' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.expense_account_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
            'lines.*.purchase_order_line_id' => ['nullable', 'integer'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $grn = $this->grnService->create($validated, auth()->id());

            return redirect()->route('accounting.goods-received-notes.show', $grn)
                ->with('success', 'Goods received note created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(GoodsReceivedNote $goodsReceivedNote)
    {
        $companyId = session('current_company_id');
        abort_unless($goodsReceivedNote->company_id == $companyId, 403);

        $goodsReceivedNote->load(['lines.product', 'lines.costCenter', 'purchaseOrder', 'vendor', 'journalEntry']);

        return view('accounting.goods-received-notes.show', ['grn' => $goodsReceivedNote]);
    }

    public function post(GoodsReceivedNote $goodsReceivedNote)
    {
        $this->requirePermission('goods-received-notes.post');
        $companyId = session('current_company_id');
        abort_unless($goodsReceivedNote->company_id == $companyId, 403);

        try {
            $this->grnService->post($goodsReceivedNote, auth()->id());

            return redirect()->route('accounting.goods-received-notes.show', $goodsReceivedNote)
                ->with('success', 'GRN posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
