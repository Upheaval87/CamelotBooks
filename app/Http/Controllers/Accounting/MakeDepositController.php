<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\MakeDepositService;
use Illuminate\Http\Request;

class MakeDepositController extends Controller
{
    public function __construct(protected MakeDepositService $depositService)
    {
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $undepositedBalance = $this->depositService->getUndepositedFundsBalance($companyId);
        $undepositedLines = $this->depositService->getUndepositedFundsLines($companyId);

        return view('accounting.deposits.index', compact('bankAccounts', 'undepositedBalance', 'undepositedLines'));
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $undepositedLines = $this->depositService->getUndepositedFundsLines($companyId);

        return view('accounting.deposits.create', compact('bankAccounts', 'undepositedLines'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:255'],
            'journal_entry_ids' => ['required', 'array', 'min:1'],
            'journal_entry_ids.*' => ['integer'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $transaction = $this->depositService->createDeposit($validated, auth()->id());

            return redirect()->route('accounting.bank-accounts.register', $validated['bank_account_id'])
                ->with('success', 'Deposit recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
