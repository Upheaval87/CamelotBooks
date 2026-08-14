<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Services\Accounting\BudgetCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $f = [
            'q' => $request->input('q'),
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'department' => $request->input('department'),
            'branch_id' => $request->input('branch_id'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $query = PurchaseRequisition::forCompany($companyId)
            ->with(['createdBy', 'requestedBy', 'approvedBy', 'branch', 'lines', 'purchaseOrder']);

        if ($f['q']) {
            $query->where(function ($q) use ($f) {
                $q->where('requisition_number', 'like', "%{$f['q']}%")
                    ->orWhere('reference', 'like', "%{$f['q']}%")
                    ->orWhere('department', 'like', "%{$f['q']}%")
                    ->orWhere('memo', 'like', "%{$f['q']}%");
            });
        }
        if ($f['status']) {
            $query->where('status', $f['status']);
        }
        if ($f['priority']) {
            $query->where('priority', $f['priority']);
        }
        if ($f['department']) {
            $query->where('department', $f['department']);
        }
        if ($f['branch_id']) {
            $query->where('branch_id', $f['branch_id']);
        }

        $this->applySort($query, $f['sort']);

        $requisitions = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = DB::table('purchase_requisitions as pr')
            ->leftJoin('purchase_requisition_lines as prl', 'prl.purchase_requisition_id', '=', 'pr.id')
            ->where('pr.company_id', $companyId)
            ->select('pr.status', DB::raw('count(distinct pr.id) as count'), DB::raw('coalesce(sum(prl.estimated_total), 0) as total'))
            ->groupBy('pr.status')
            ->get()
            ->keyBy('status');

        $statsTotal = $stats->sum('count');
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $departments = PurchaseRequisition::forCompany($companyId)
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('accounting.purchase-requisitions.index', compact('requisitions', 'stats', 'statsTotal', 'branches', 'departments', 'f'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        return view('accounting.purchase-requisitions.create', $this->formData($companyId));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate($this->rules());

        $action = $request->input('action', 'save');

        DB::beginTransaction();

        try {
            $requisition = PurchaseRequisition::create([
                'company_id' => $companyId,
                'branch_id' => $validated['branch_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'requisition_number' => $this->generateRequisitionNumber($companyId),
                'date' => $validated['date'],
                'required_by' => $validated['required_by'] ?? null,
                'priority' => $validated['priority'] ?? PurchaseRequisition::PRIORITY_NORMAL,
                'requested_by' => auth()->id(),
                'department' => $validated['department'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'memo' => $validated['memo'] ?? null,
                'status' => PurchaseRequisition::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $this->storeLines($requisition, $validated['lines']);

            if ($action === 'submit_for_approval') {
                $requisition->update([
                    'status' => PurchaseRequisition::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        if ($action === 'save_and_new') {
            return redirect()->route('accounting.purchase-requisitions.create')
                ->with('success', 'Purchase requisition created successfully.');
        }

        $message = $action === 'submit_for_approval'
            ? 'Purchase requisition submitted for approval.'
            : 'Purchase requisition created successfully.';

        return redirect()->route('accounting.purchase-requisitions.show', $requisition)
            ->with('success', $message);
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        $purchaseRequisition->load([
            'lines.product',
            'lines.costCenter',
            'lines.expenseAccount',
            'createdBy',
            'requestedBy',
            'approvedBy',
            'branch',
            'costCenter',
            'purchaseOrder',
        ]);

        $budgetCheck = app(BudgetCheckService::class)->check(
            $companyId,
            $purchaseRequisition->lines->map(fn ($line) => [
                'expense_account_id' => $line->expense_account_id,
                'estimated_total' => $line->estimated_total,
            ])->all(),
            $purchaseRequisition->date->format('Y-m-d')
        );

        $canDecide = auth()->user()->can('purchase-requisitions.approve')
            && (int) $purchaseRequisition->created_by !== (int) auth()->id();

        return view('accounting.purchase-requisitions.show', compact('purchaseRequisition', 'budgetCheck', 'canDecide') + ['requisition' => $purchaseRequisition]);
    }

    public function edit(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            abort(403, 'Only draft requisitions can be edited.');
        }

        $purchaseRequisition->load(['lines.product', 'lines.costCenter', 'lines.expenseAccount']);

        return view('accounting.purchase-requisitions.edit', array_merge(
            ['requisition' => $purchaseRequisition],
            $this->formData($companyId)
        ));
    }

    public function update(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            abort(403, 'Only draft requisitions can be updated.');
        }

        $validated = $request->validate($this->rules());

        $action = $request->input('action', 'save');

        DB::beginTransaction();

        try {
            $purchaseRequisition->update([
                'branch_id' => $validated['branch_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'date' => $validated['date'],
                'required_by' => $validated['required_by'] ?? null,
                'priority' => $validated['priority'] ?? PurchaseRequisition::PRIORITY_NORMAL,
                'department' => $validated['department'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'memo' => $validated['memo'] ?? null,
            ]);

            $purchaseRequisition->lines()->delete();
            $this->storeLines($purchaseRequisition, $validated['lines']);

            if ($action === 'submit_for_approval') {
                $purchaseRequisition->update([
                    'status' => PurchaseRequisition::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        $message = $action === 'submit_for_approval'
            ? 'Requisition updated and submitted for approval.'
            : 'Purchase requisition updated successfully.';

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', $message);
    }

    public function destroy(PurchaseRequisition $purchaseRequisition)
    {
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
                ->with('error', 'Only draft requisitions can be deleted.');
        }

        DB::transaction(function () use ($purchaseRequisition) {
            $purchaseRequisition->lines()->delete();
            $purchaseRequisition->delete();
        });

        return redirect()->route('accounting.purchase-requisitions.index')
            ->with('success', 'Purchase requisition deleted.');
    }

    public function submit(PurchaseRequisition $purchaseRequisition)
    {
        $this->requirePermission('purchase-requisitions.submit');
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_DRAFT) {
            return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
                ->withErrors(['error' => 'Only draft requisitions can be submitted.']);
        }

        $purchaseRequisition->update([
            'status' => PurchaseRequisition::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Requisition submitted for approval.');
    }

    public function approve(PurchaseRequisition $purchaseRequisition)
    {
        $this->requirePermission('purchase-requisitions.approve');
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
                ->withErrors(['error' => 'Only submitted requisitions can be approved.']);
        }

        $purchaseRequisition->update([
            'status' => PurchaseRequisition::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Requisition approved.');
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $this->requirePermission('purchase-requisitions.reject');
        $companyId = session('current_company_id');
        abort_unless($purchaseRequisition->company_id == $companyId, 403);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
                ->withErrors(['error' => 'Only submitted requisitions can be rejected.']);
        }

        $validated = $request->validate([
            'rejected_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseRequisition->update([
            'status' => PurchaseRequisition::STATUS_REJECTED,
            'rejected_reason' => $validated['rejected_reason'] ?? null,
        ]);

        return redirect()->route('accounting.purchase-requisitions.show', $purchaseRequisition)
            ->with('success', 'Requisition rejected.');
    }

    public function budgetCheck(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'lines' => ['nullable', 'array'],
            'lines.*.expense_account_id' => ['nullable', 'integer'],
            'lines.*.estimated_total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $check = app(BudgetCheckService::class)->check(
            $companyId,
            $validated['lines'] ?? [],
            $validated['date']
        );

        return response()->json([
            'status' => $check['status'],
            'message' => $check['message'],
            'total_budgeted' => $check['total_budgeted'],
            'total_spent' => $check['total_spent'],
            'total_requested' => $check['total_requested'],
            'total_available' => $check['total_available'],
            'accounts' => $check['accounts'],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $this->requirePermission($request, 'purchase-requisitions.view');

        $companyId = session('current_company_id');

        $query = PurchaseRequisition::forCompany($companyId)->with('lines');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('requisition_number', 'like', "%{$request->q}%")
                    ->orWhere('memo', 'like', "%{$request->q}%");
            });
        }

        $this->applySort($query, $request->input('sort', 'newest'));
        $requisitions = $query->get();

        $headers = ['Requisition #', 'Date', 'Priority', 'Department', 'Status', 'Subtotal', 'Est. Tax', 'Grand Total', 'Required By', 'Reference', 'Supplier'];

        $filename = 'purchase-requisitions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($headers, $requisitions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($requisitions as $requisition) {
                fputcsv($out, [
                    $requisition->requisition_number,
                    $requisition->date?->format('Y-m-d'),
                    ucfirst($requisition->priority ?? 'normal'),
                    $requisition->department ?? '',
                    $requisition->statusLabel(),
                    number_format($requisition->subtotal(), 2),
                    number_format($requisition->estimatedTax(), 2),
                    number_format($requisition->grandTotal(), 2),
                    $requisition->required_by ?? '',
                    $requisition->reference ?? '',
                    $requisition->supplier ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'required_by' => ['nullable', 'date', 'after_or_equal:date'],
            'priority' => ['nullable', 'in:normal,urgent'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'supplier' => ['nullable', 'string', 'max:200'],
            'reference' => ['nullable', 'string', 'max:60'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.estimated_total' => ['nullable', 'numeric', 'min:0'],
            'lines.*.expense_account_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
        ];
    }

    protected function storeLines(PurchaseRequisition $requisition, array $lines): void
    {
        foreach ($lines as $lineData) {
            $estimatedTotal = null;

            if (isset($lineData['quantity']) && !empty($lineData['estimated_unit_cost'])) {
                $estimatedTotal = round((float) $lineData['quantity'] * (float) $lineData['estimated_unit_cost'], 2);
            } elseif (!empty($lineData['estimated_total'])) {
                $estimatedTotal = round((float) $lineData['estimated_total'], 2);
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
    }

    protected function formData(int $companyId): array
    {
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        $costCenters = CostCenter::forCompany($companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $accounts = Account::forCompany($companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::forCompany($companyId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->orderBy('code')
            ->get();

        $products = Product::forCompany($companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $departments = PurchaseRequisition::forCompany($companyId)
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $defaultExpenseAccountId = $expenseAccounts->first()?->id;

        return compact('branches', 'costCenters', 'accounts', 'expenseAccounts', 'products', 'departments', 'defaultExpenseAccountId');
    }

    protected function applySort($query, string $sort): void
    {
        switch ($sort) {
            case 'total_high':
                $query->leftJoin('purchase_requisition_lines as prl_sort', 'prl_sort.purchase_requisition_id', '=', 'purchase_requisitions.id')
                    ->select('purchase_requisitions.*', DB::raw('coalesce(sum(prl_sort.estimated_total), 0) as prl_sort_total'))
                    ->groupBy('purchase_requisitions.id')
                    ->orderByDesc('prl_sort_total');
                break;

            case 'needed_by':
                $query->orderByRaw('required_by is null')->orderBy('required_by');
                break;

            default:
                $query->orderByDesc('date');
                break;
        }
    }

    protected function generateRequisitionNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'REQ-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = PurchaseRequisition::forCompany($companyId)
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
