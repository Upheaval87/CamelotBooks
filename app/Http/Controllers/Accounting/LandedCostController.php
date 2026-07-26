<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\GoodsReceivedNote;
use App\Models\LandedCostVoucher;
use App\Models\Vendor;
use App\Services\Inventory\LandedCostAllocationService;
use Illuminate\Http\Request;

class LandedCostController extends Controller
{
    protected LandedCostAllocationService $service;

    public function __construct(LandedCostAllocationService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $vouchers = LandedCostVoucher::where('company_id', $companyId)
            ->with('vendor')
            ->orderByDesc('date')
            ->get();

        return view('accounting.landed-costs.index', compact('vouchers'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $grns = GoodsReceivedNote::where('company_id', $companyId)
            ->where('status', GoodsReceivedNote::STATUS_POSTED)
            ->with('lines.product')
            ->orderByDesc('date')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.landed-costs.create', compact('vendors', 'grns', 'accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'allocation_method' => 'required|in:by_value,by_quantity',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'grn_ids' => 'required|array|min:1',
            'grn_ids.*' => 'exists:goods_received_notes,id',
            'components' => 'required|array|min:1',
            'components.*.component_type' => 'required|in:freight,customs,insurance,handling,other',
            'components.*.description' => 'required|string|max:255',
            'components.*.amount' => 'required|numeric|min:0.01',
            'components.*.payee_account_id' => 'required|exists:accounts,id',
        ]);

        $validated['company_id'] = $companyId;

        $voucher = $this->service->create($validated, $userId);

        return redirect()->route('accounting.landed-costs.show', $voucher)
            ->with('success', 'Landed cost voucher created successfully.');
    }

    public function show(LandedCostVoucher $voucher)
    {
        if ($voucher->company_id !== session('current_company_id')) {
            abort(404);
        }

        $voucher->load(['vendor', 'components.payeeAccount', 'grns.lines.product', 'journalEntry.lines.account']);

        return view('accounting.landed-costs.show', compact('voucher'));
    }

    public function post(LandedCostVoucher $voucher)
    {
        if ($voucher->company_id !== session('current_company_id')) {
            abort(404);
        }

        $userId = auth()->id();

        $this->service->post($voucher, $userId);

        return redirect()->route('accounting.landed-costs.show', $voucher)
            ->with('success', 'Landed cost voucher posted successfully.');
    }
}
