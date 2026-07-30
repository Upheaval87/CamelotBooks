<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\PettyCashService;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    public function __construct(protected PettyCashService $pettyCashService)
    {
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $summary = $this->pettyCashService->getFundSummary($companyId);

        return view('accounting.petty-cash.index', compact('summary'));
    }

    public function createFund()
    {
        return view('accounting.petty-cash.create-fund');
    }

    public function storeFund(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $fund = $this->pettyCashService->createFund($validated, auth()->id());

            return redirect()->route('accounting.petty-cash.show', $fund->id)
                ->with('success', 'Petty cash fund created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(int $fundId)
    {
        $companyId = session('current_company_id');

        $fund = Account::where('id', $fundId)
            ->where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->first();

        abort_unless($fund, 404);

        $expenses = $this->pettyCashService->getExpenses($companyId, $fundId);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('type', ['expense'])
            ->orderBy('code')
            ->get();

        return view('accounting.petty-cash.show', compact('fund', 'expenses', 'bankAccounts', 'expenseAccounts'));
    }

    public function establish(Request $request)
    {
        $this->requirePermission($request, 'petty-cash.establish');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'fund_id' => ['required', 'integer'],
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
        ]);

        $fund = Account::where('id', $validated['fund_id'])
            ->where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->first();

        abort_unless($fund, 404);

        try {
            $this->pettyCashService->establishFund($fund, $validated['bank_account_id'], $validated['amount'], $validated['date'], auth()->id());

            return redirect()->route('accounting.petty-cash.show', $fund->id)
                ->with('success', 'Petty cash fund established successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function recordExpense(Request $request)
    {
        $this->requirePermission($request, 'petty-cash.expense');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'petty_cash_account_id' => ['required', 'integer'],
            'debit_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $result = $this->pettyCashService->recordExpense($validated, auth()->id());

            return redirect()->route('accounting.petty-cash.show', $validated['petty_cash_account_id'])
                ->with('success', 'Expense recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function replenish(Request $request)
    {
        $this->requirePermission($request, 'petty-cash.replenish');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'petty_cash_account_id' => ['required', 'integer'],
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $this->pettyCashService->replenishFund($validated, auth()->id());

            return redirect()->route('accounting.petty-cash.show', $validated['petty_cash_account_id'])
                ->with('success', 'Petty cash fund replenished successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
