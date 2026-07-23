<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = PurchaseRequisition::where('company_id', $companyId)
            ->with(['createdBy', 'approvedBy', 'lines']);

        if ($request->filled('status')) {
            $query->forStatus($request->status);
        }

        $requisitions = $query->orderByDesc('date')->paginate(15)->withQueryString();

        return view('accounting.purchase-requisitions.index', compact('requisitions'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

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

        return view('accounting.purchase-requisitions.create', compact('products', 'accounts', 'costCenters', 'branches'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.expense_account_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
        ]);

        DB::beginTransaction();

        try {
            $requisitionNumber = $this->generateRequisitionNumber($companyId);

            $requisition = PurchaseRequisition::create([
                'company_id' => $companyId,
                'branch_id' => $validated['branch_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'requisition_number' => $requisitionNumber,
                'date' => $validated['date'],
                'status' => PurchaseRequisition::STATUS_DRAFT,
                'memo' => $validated['memo'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['lines'] as $lineData) {
                $estimatedTotal = null;
                if (!empty($lineData['estimated_unit_cost']) && !empty($lineData['quantity'])) {
                    $estimatedTotal = round($lineData['estimated_unit_cost'] * $lineData['quantity'], 2);
                }

                PurchaseRequisitionLine::create([
                    'purchase_requisition_id' => $requisition->id,
                    'product_id' => $lineData['product_id'] ?? null,
                    'description' => $lineData['description'],
                    'quantity' => $lineData['quantity'],
                    'estimated_unit_cost' => $lineData['estimated_unit_cost'] ?? null,
                    'estimated_total' => $estimatedTotal,
                    'expense_account_id' => $lineData['expense_account_id'] ?? null,
                    'cost_center_id' => $lineData['cost_center_id'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.purchase-requisitions.show', $requisition)
                ->with('success', 'Purchase requisition created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        $purchaseRequisition->load(['lines.product', 'lines.costCenter', 'createdBy', 'approvedBy', 'branch']);

        return view('accounting.purchase-requisitions.show', ['requisition' => $purchaseRequisition]);
    }

    public function edit(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            abort(403, 'Only draft requisitions can be edited.');
        }

        $purchaseRequisition->load('lines');

        $companyId = session('current_company_id');

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

        return view('accounting.purchase-requisitions.edit', [
            'requisition' => $purchaseRequisition,
            'products' => $products,
            'accounts' => $accounts,
            'costCenters' => $costCenters,
            'branches' => $branches,
        ]);
    }

    public function update(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            abort(403, 'Only draft requisitions can be updated.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.expense_account_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
        ]);

        DB::beginTransaction();

        try {
            $purchaseRequisition->update([
                'date' => $validated['date'],
                'branch_id' => $validated['branch_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'memo' => $validated['memo'] ?? null,
            ]);

            $purchaseRequisition->lines()->delete();

            foreach ($validated['lines'] as $lineData) {
                $estimatedTotal = null;
                if (!empty($lineData['estimated_unit_cost']) && !empty($lineData['quantity'])) {
                    $estimatedTotal = round($lineData['estimated_unit_cost'] * $lineData['quantity'], 2);
                }

                PurchaseRequisitionLine::create([
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'product_id' => $lineData['product_id'] ?? null,
                    'description' => $lineData['description'],
                    'quantity' => $lineData['quantity'],
                    'estimated_unit_cost' => $lineData['estimated_unit_cost'] ?? null,
                    'estimated_total' => $estimatedTotal,
                    'expense_account_id' => $lineData['expense_account_id'] ?? null,
                    'cost_center_id' => $lineData['cost_center_id'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
                ->with('success', 'Purchase requisition updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function submit(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            return redirect()->back()->withErrors(['error' => 'Only draft requisitions can be submitted.']);
        }

        $purchaseRequisition->update(['status' => PurchaseRequisition::STATUS_SUBMITTED]);

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Requisition submitted for approval.');
    }

    public function approve(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return redirect()->back()->withErrors(['error' => 'Only submitted requisitions can be approved.']);
        }

        $purchaseRequisition->update([
            'status' => PurchaseRequisition::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Requisition approved.');
    }

    public function reject(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return redirect()->back()->withErrors(['error' => 'Only submitted requisitions can be rejected.']);
        }

        $purchaseRequisition->update(['status' => PurchaseRequisition::STATUS_REJECTED]);

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Requisition rejected.');
    }

    protected function generateRequisitionNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'REQ-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = PurchaseRequisition::where('company_id', $companyId)
            ->where('requisition_number', 'like', $prefix . '%')
            ->orderByDesc('requisition_number')
            ->first();

        if ($last) {
            $lastSequence = (int) substr($last->requisition_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }
}
