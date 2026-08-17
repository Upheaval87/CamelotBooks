<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Services\Accounting\BankService;
use Illuminate\Http\Request;

class BankingTransferController extends Controller
{
    public function __construct(protected BankService $bankService)
    {
    }

    public function index()
    {
        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $transfers = BankTransaction::where('company_id', $companyId)
            ->where('source_type', 'transfer')
            ->with('bankAccount', 'journalEntry')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return view('accounting.banking.transfers', compact('bankAccounts', 'transfers'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.banking.transfer-form', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'bank-accounts.create');
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->bankService->transfer(
                $validated['from_account_id'],
                $validated['to_account_id'],
                (float) $validated['amount'],
                $validated['date'],
                $validated['description'],
                $companyId,
                auth()->id()
            );

            return redirect()->route('accounting.banking.transfers')->with('success', 'Transfer completed successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
