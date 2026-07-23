<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Services\Accounting\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReconciliationController extends Controller
{
    public function __construct(protected ReconciliationService $reconciliationService)
    {
    }

    public function index(int $bankAccountId)
    {
        $companyId = session('current_company_id');

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        $reconciliations = BankReconciliation::where('company_id', $companyId)
            ->where('bank_account_id', $bankAccountId)
            ->with('import')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('accounting.bank-reconciliation.index', compact('bankAccount', 'reconciliations'));
    }

    public function importForm(int $bankAccountId)
    {
        $companyId = session('current_company_id');

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        return view('accounting.bank-reconciliation.import', compact('bankAccount'));
    }

    public function import(Request $request, int $bankAccountId)
    {
        $companyId = session('current_company_id');

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        $validated = $request->validate([
            'statement_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'statement_date' => ['required', 'date'],
            'statement_end_balance' => ['required', 'numeric'],
        ]);

        try {
            $csvContent = file_get_contents($validated['statement_file']->getRealPath());
            $filename = $validated['statement_file']->getClientOriginalName();

            $import = $this->reconciliationService->importStatement([
                'bank_account_id' => $bankAccountId,
                'company_id' => $companyId,
                'statement_date' => $validated['statement_date'],
                'statement_end_balance' => $validated['statement_end_balance'],
                'filename' => $filename,
            ], $csvContent, auth()->id());

            $reconciliation = $this->reconciliationService->startReconciliation(
                $bankAccountId,
                $import->id,
                $companyId
            );

            return redirect()->route('accounting.bank-reconciliation.show', $reconciliation->id)
                ->with('success', 'Statement imported. Reconciliation started.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->with('bankAccount', 'import')
            ->first();

        abort_unless($reconciliation, 404);

        $unmatchedStatementLines = BankStatementLine::where('import_id', $reconciliation->import_id)
            ->where('is_matched', false)
            ->orderBy('transaction_date')
            ->get();

        $unreconciledTransactions = BankTransaction::where('bank_account_id', $reconciliation->bank_account_id)
            ->where('company_id', $companyId)
            ->where('is_reconciled', false)
            ->orderBy('date')
            ->get();

        $matchedItems = BankReconciliationItem::where('reconciliation_id', $reconciliationId)
            ->with('bankStatementLine', 'bankTransaction')
            ->get();

        $summary = $this->reconciliationService->getReconciliationSummary($reconciliationId);

        return view('accounting.bank-reconciliation.show', compact(
            'reconciliation',
            'unmatchedStatementLines',
            'unreconciledTransactions',
            'matchedItems',
            'summary'
        ));
    }

    public function suggestMatches(int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($reconciliation, 404);

        try {
            $suggestions = $this->reconciliationService->suggestMatches($reconciliationId);

            return response()->json(['suggestions' => $suggestions]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function match(Request $request, int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($reconciliation, 404);

        $validated = $request->validate([
            'matches' => ['required', 'array', 'min:1'],
            'matches.*.bank_statement_line_id' => ['nullable', 'integer'],
            'matches.*.bank_transaction_id' => ['nullable', 'integer'],
            'matches.*.amount' => ['required', 'numeric'],
        ]);

        try {
            $this->reconciliationService->matchItems($reconciliationId, $validated['matches']);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function unmatch(Request $request, int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($reconciliation, 404);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:bank_reconciliation_items,id'],
        ]);

        try {
            $this->reconciliationService->unmatchItem($validated['item_id']);

            return response()->json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function createTransactionForm(int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->with('bankAccount')
            ->first();

        abort_unless($reconciliation, 404);

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_bank_account', false)
            ->orderBy('code')
            ->get();

        return view('accounting.bank-reconciliation.create-transaction', compact('reconciliation', 'accounts'));
    }

    public function createTransaction(Request $request, int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($reconciliation, 404);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:fee,withdrawal,deposit,interest'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'debit_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'credit_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        $validated['bank_account_id'] = $reconciliation->bank_account_id;
        $validated['company_id'] = $companyId;

        try {
            $transaction = app(\App\Services\Accounting\BankService::class)
                ->createManualTransaction($validated, auth()->id());

            $this->reconciliationService->matchItems($reconciliationId, [
                [
                    'bank_statement_line_id' => null,
                    'bank_transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                ],
            ]);

            return response()->json(['success' => true, 'transaction_id' => $transaction->id]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function complete(int $reconciliationId)
    {
        $companyId = session('current_company_id');

        $reconciliation = BankReconciliation::where('id', $reconciliationId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($reconciliation, 404);

        try {
            $this->reconciliationService->completeReconciliation($reconciliationId, auth()->id());

            return redirect()->route('accounting.bank-reconciliation.index', $reconciliation->bank_account_id)
                ->with('success', 'Reconciliation completed successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
