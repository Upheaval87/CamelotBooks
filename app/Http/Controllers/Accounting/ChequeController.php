<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Cheque;
use App\Services\Accounting\ChequeService;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function __construct(protected ChequeService $chequeService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $bankAccountId = $request->input('bank_account_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $cheques = $this->chequeService->getRegister($companyId, $bankAccountId, $fromDate, $toDate);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        return view('accounting.cheques.index', compact('cheques', 'bankAccounts', 'bankAccountId', 'fromDate', 'toDate'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('type', ['expense', 'asset'])
            ->orderBy('code')
            ->get();

        return view('accounting.cheques.create', compact('bankAccounts', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'debit_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'memo' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $cheque = $this->chequeService->writeCheque($validated, auth()->id());

            return redirect()->route('accounting.cheques.show', $cheque->id)
                ->with('success', 'Cheque #' . str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) . ' written successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(int $chequeId)
    {
        $companyId = session('current_company_id');

        $cheque = Cheque::where('id', $chequeId)
            ->where('company_id', $companyId)
            ->with('bankAccount', 'journalEntry', 'createdBy', 'voidedBy')
            ->first();

        abort_unless($cheque, 404);

        return view('accounting.cheques.show', compact('cheque'));
    }

    public function voidCheque(int $chequeId)
    {
        $this->requirePermission('cheques.void');
        $companyId = session('current_company_id');

        $cheque = Cheque::where('id', $chequeId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($cheque, 404);

        try {
            $this->chequeService->voidCheque($cheque, auth()->id());

            return redirect()->route('accounting.cheques.show', $cheque->id)
                ->with('success', 'Cheque voided successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function register(Request $request)
    {
        $companyId = session('current_company_id');

        $bankAccountId = $request->input('bank_account_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $cheques = $this->chequeService->getRegister($companyId, $bankAccountId, $fromDate, $toDate);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        return view('accounting.cheques.register', compact('cheques', 'bankAccounts', 'bankAccountId', 'fromDate', 'toDate'));
    }
}
