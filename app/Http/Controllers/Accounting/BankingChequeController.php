<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Cheque;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntryLine;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankingChequeController extends Controller
{
    public function __construct(protected JournalPostingEngine $postingEngine)
    {
    }

    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $bankAccountId = $request->input('bank_account_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $cheques = $this->chequeRegister($companyId, $bankAccountId, $fromDate, $toDate);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        return view('accounting.banking.cheques', compact('cheques', 'bankAccounts', 'bankAccountId', 'fromDate', 'toDate'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

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

        return view('accounting.banking.cheque-form', compact('bankAccounts', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'cheques.create');
        $companyId = (int) session('current_company_id');

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
            $cheque = $this->writeCheque($validated, auth()->id());

            return redirect()->route('accounting.banking.cheques.show', $cheque->id)
                ->with('success', 'Cheque #' . str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) . ' written successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(int $chequeId)
    {
        $companyId = (int) session('current_company_id');

        $cheque = Cheque::where('id', $chequeId)
            ->where('company_id', $companyId)
            ->with('bankAccount', 'journalEntry', 'createdBy', 'voidedBy')
            ->first();

        abort_unless($cheque, 404);

        return view('accounting.banking.cheque-show', compact('cheque'));
    }

    public function void(int $chequeId)
    {
        $this->requirePermission('cheques.void');
        $companyId = (int) session('current_company_id');

        $cheque = Cheque::where('id', $chequeId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($cheque, 404);

        try {
            $this->voidCheque($cheque, auth()->id());

            return redirect()->route('accounting.banking.cheques.show', $cheque->id)
                ->with('success', 'Cheque voided successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function clear(int $chequeId)
    {
        $this->requirePermission('cheques.edit');
        $companyId = (int) session('current_company_id');

        $cheque = Cheque::where('id', $chequeId)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($cheque, 404);

        try {
            $this->clearCheque($cheque, auth()->id());

            return redirect()->route('accounting.banking.cheques.show', $cheque->id)
                ->with('success', 'Cheque marked as cleared.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function writeCheque(array $data, int $userId): Cheque
    {
        foreach (['company_id', 'bank_account_id', 'date', 'payee', 'amount', 'debit_account_id'] as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $bankAccountId = $data['bank_account_id'];
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new InvalidArgumentException('Cheque amount must be greater than zero.');
        }

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (! $bankAccount) {
            throw new InvalidArgumentException("Bank account ID {$bankAccountId} is not a valid bank account.");
        }

        $debitAccount = Account::where('id', $data['debit_account_id'])
            ->where('company_id', $companyId)
            ->first();

        if (! $debitAccount) {
            throw new InvalidArgumentException("Debit account ID {$data['debit_account_id']} not found.");
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $bankAccountId, $amount, $debitAccount) {
            $chequeNumber = $this->getNextChequeNumber($companyId, $bankAccountId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'cheque',
                'reference' => 'CHQ-' . str_pad($chequeNumber, 6, '0', STR_PAD_LEFT),
                'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}",
                'lines' => [
                    ['account_id' => $debitAccount->id, 'debit' => $amount, 'credit' => 0, 'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}"],
                    ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => $amount, 'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}"],
                ],
            ]);

            $bankTx = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'withdrawal',
                'source_type' => 'cheque',
                'source_id' => $journalEntry->id,
                'date' => $data['date'],
                'description' => "Cheque #{$chequeNumber} to {$data['payee']}",
                'reference' => 'CHQ-' . str_pad($chequeNumber, 6, '0', STR_PAD_LEFT),
                'amount' => -$amount,
                'created_by' => $userId,
            ]);

            return Cheque::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'cheque_number' => $chequeNumber,
                'date' => $data['date'],
                'payee' => $data['payee'],
                'memo' => $data['memo'] ?? null,
                'amount' => $amount,
                'status' => Cheque::STATUS_OUTSTANDING,
                'source_type' => 'bank_transaction',
                'source_id' => $bankTx->id,
                'journal_entry_id' => $journalEntry->id,
                'created_by' => $userId,
            ]);
        });
    }

    protected function voidCheque(Cheque $cheque, int $userId): Cheque
    {
        if ($cheque->status === Cheque::STATUS_VOID) {
            throw new InvalidArgumentException('This cheque is already void.');
        }

        $companyId = $cheque->company_id;

        return DB::transaction(function () use ($cheque, $userId, $companyId) {
            $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => now()->format('Y-m-d'),
                'source_module' => 'cheque_void',
                'reference' => 'VOID-CHQ-' . str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT),
                'memo' => "Void of cheque #{$cheque->cheque_number}",
                'lines' => [
                    ['account_id' => $cheque->bank_account_id, 'debit' => $cheque->amount, 'credit' => 0, 'memo' => "Void of cheque #{$cheque->cheque_number}"],
                    ['account_id' => $this->getDebitAccountForCheque($cheque)->id, 'debit' => 0, 'credit' => $cheque->amount, 'memo' => "Void of cheque #{$cheque->cheque_number}"],
                ],
            ]);

            $cheque->update([
                'status' => Cheque::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $userId,
            ]);

            if ($cheque->source_type === 'bank_transaction' && $cheque->source_id) {
                BankTransaction::where('id', $cheque->source_id)->delete();
            }

            return $cheque;
        });
    }

    protected function clearCheque(Cheque $cheque, int $userId): Cheque
    {
        if ($cheque->status !== Cheque::STATUS_OUTSTANDING) {
            throw new InvalidArgumentException('Only outstanding cheques can be marked as cleared.');
        }

        $cheque->update(['status' => Cheque::STATUS_CLEARED]);

        if ($cheque->source_type === 'bank_transaction' && $cheque->source_id) {
            BankTransaction::where('id', $cheque->source_id)
                ->update([
                    'is_reconciled' => true,
                    'reconciled_at' => now(),
                ]);
        }

        return $cheque;
    }

    protected function chequeRegister(int $companyId, ?int $bankAccountId = null, ?string $fromDate = null, ?string $toDate = null): \Illuminate\Support\Collection
    {
        $query = Cheque::where('company_id', $companyId)
            ->with('bankAccount')
            ->orderBy('cheque_number', 'asc');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($fromDate) {
            $query->where('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('date', '<=', $toDate);
        }

        return $query->get();
    }

    protected function getNextChequeNumber(int $companyId, int $bankAccountId): int
    {
        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->first();

        $nextNumber = $bankAccount->next_cheque_number ?? 1;

        $bankAccount->update(['next_cheque_number' => $nextNumber + 1]);

        return $nextNumber;
    }

    protected function getDebitAccountForCheque(Cheque $cheque): Account
    {
        if ($cheque->journal_entry_id) {
            $line = JournalEntryLine::where('journal_entry_id', $cheque->journal_entry_id)
                ->where('debit', '>', 0)
                ->first();

            if ($line) {
                return Account::find($line->account_id);
            }
        }

        return DefaultAccountMapping::getAccount($cheque->company_id, 'default_expense');
    }
}
