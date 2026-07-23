<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Services\Accounting\BudgetVarianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $budgets = Budget::where('company_id', $companyId)
            ->with('fiscalYear')
            ->latest()
            ->get();

        return view('accounting.budgets.index', compact('budgets'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $fiscalYears = FiscalYear::where('company_id', $companyId)->orderByDesc('start_date')->get();
        $accounts = \App\Models\Account::where('company_id', $companyId)
            ->whereIn('type', ['income', 'expense'])
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.budgets.create', compact('fiscalYears', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $companyId = session('current_company_id');

        $budget = Budget::create([
            'company_id' => $companyId,
            'fiscal_year_id' => $request->fiscal_year_id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => Budget::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        $this->saveLines($budget, $request);

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget created successfully.');
    }

    public function show(Budget $budget)
    {
        $companyId = session('current_company_id');
        abort_unless($budget->company_id == $companyId, 403);

        $budget->load('fiscalYear', 'lines.account');

        return view('accounting.budgets.show', compact('budget'));
    }

    public function edit(Budget $budget)
    {
        $companyId = session('current_company_id');
        abort_unless($budget->company_id == $companyId, 403);

        if ($budget->status === Budget::STATUS_APPROVED) {
            abort(403, 'Approved budgets cannot be edited.');
        }

        $budget->load('lines.account');
        $fiscalYears = FiscalYear::where('company_id', $companyId)->orderByDesc('start_date')->get();
        $accounts = \App\Models\Account::where('company_id', $companyId)
            ->whereIn('type', ['income', 'expense'])
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.budgets.edit', compact('budget', 'fiscalYears', 'accounts'));
    }

    public function update(Request $request, Budget $budget)
    {
        $companyId = session('current_company_id');
        abort_unless($budget->company_id == $companyId, 403);

        if ($budget->status === Budget::STATUS_APPROVED) {
            abort(403, 'Approved budgets cannot be edited.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $budget->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $budget->lines()->delete();
        $this->saveLines($budget, $request);

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget updated successfully.');
    }

    public function approve(Budget $budget)
    {
        $companyId = session('current_company_id');
        abort_unless($budget->company_id == $companyId, 403);

        $budget->update([
            'status' => Budget::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget approved.');
    }

    public function variance(Budget $budget)
    {
        $companyId = session('current_company_id');
        abort_unless($budget->company_id == $companyId, 403);

        $service = app(BudgetVarianceService::class);
        $report = $service->generateVarianceReport($budget);

        return view('accounting.budgets.variance', $report);
    }

    private function saveLines(Budget $budget, Request $request): void
    {
        if (!$request->has('lines')) {
            return;
        }

        foreach ($request->lines as $lineData) {
            if (empty($lineData['account_id']) || empty($lineData['period_label'])) {
                continue;
            }

            $amount = (float) ($lineData['amount'] ?? 0);
            if ($amount == 0) {
                continue;
            }

            BudgetLine::create([
                'budget_id' => $budget->id,
                'account_id' => $lineData['account_id'],
                'period_label' => $lineData['period_label'],
                'amount' => $amount,
                'notes' => $lineData['notes'] ?? null,
            ]);
        }
    }
}
