<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosReturnable;
use App\Models\Product;
use App\Services\POS\PosReturnableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosReturnableController extends Controller
{
    public function __construct(
        private PosReturnableService $returnableService,
    ) {}

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = Auth::id();
        $isHeadOffice = $this->isHeadOffice($companyId);

        $query = PosReturnable::where('company_id', $companyId)
            ->with(['product', 'customer', 'branch'])
            ->latest();

        if (!$isHeadOffice) {
            $branchId = session('pos_terminal_branch_id');
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('intake_number', 'like', "%{$q}%")
                  ->orWhere('brr_number', 'like', "%{$q}%")
                  ->orWhereHas('customer', fn ($cw) => $cw->where('name', 'like', "%{$q}%"));
            });
        }

        $returnables = $query->paginate(25)->withQueryString();

        $companyId = session('current_company_id');
        $statsQuery = PosReturnable::where('company_id', $companyId);
        if (!$isHeadOffice) {
            $statsQuery->where('branch_id', session('pos_terminal_branch_id'));
        }
        $stats = [
            'total_count' => $statsQuery->count(),
            'pending_count' => (clone $statsQuery)->where('status', PosReturnable::STATUS_PENDING)->count(),
            'redeemed_count' => (clone $statsQuery)->where('status', PosReturnable::STATUS_REDEEMED)->count(),
            'total_credit' => (clone $statsQuery)->sum('credit_amount'),
        ];

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('pos.returnables.index', compact('returnables', 'stats', 'products'));
    }

    public function intake()
    {
        $companyId = session('current_company_id');
        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('pos.returnables.intake', compact('products'));
    }

    public function storeIntake(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:customers,id',
            'bottle_count' => 'required|integer|min:1|max:9999',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = $companyId;
        $validated['branch_id'] = session('pos_terminal_branch_id');
        $validated['quantity'] = $validated['bottle_count'];

        try {
            $returnable = $this->returnableService->intake($validated, $userId);

            return redirect()->route('pos.returnables.show', $returnable->id)
                ->with('success', "BRR-{$returnable->brr_number} issued. " .
                    number_format($returnable->bottle_count) . " container(s), credit " .
                    number_format($returnable->credit_amount, 2) . ".");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $companyId = session('current_company_id');
        $returnable = PosReturnable::where('company_id', $companyId)
            ->with(['product', 'customer', 'branch', 'createdBy'])
            ->findOrFail($id);

        return view('pos.returnables.show', compact('returnable'));
    }

    public function print(int $id)
    {
        $companyId = session('current_company_id');
        $returnable = PosReturnable::where('company_id', $companyId)
            ->with(['product', 'customer', 'branch', 'createdBy'])
            ->findOrFail($id);

        return view('pos.returnables.print', compact('returnable'));
    }

    public function void(Request $request, int $id)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        try {
            $this->returnableService->void($id, $companyId, $userId);

            return back()->with('success', 'BRR receipt voided. Journal entry reversed.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function creditCheck(Request $request)
    {
        $companyId = session('current_company_id');
        $customerId = $request->input('customer_id');

        if (!$customerId) {
            return response()->json([
                'available_credit' => 0,
                'receipt_count' => 0,
            ]);
        }

        $credit = $this->returnableService->availableCredit($companyId, $customerId);

        return response()->json($credit);
    }

    private function isHeadOffice(int $companyId): bool
    {
        $branchId = session('pos_terminal_branch_id');
        if (!$branchId) {
            return false;
        }
        $branch = \App\Models\Branch::find($branchId);
        return $branch && str_contains($branch->name, 'Head Office');
    }
}
