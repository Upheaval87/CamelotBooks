<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntryLine;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankingDepositController extends Controller
{
    public function __construct(protected JournalPostingEngine $postingEngine)
    {
    }

    public function index()
    {
        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $undepositedBalance = $this->undepositedFundsBalance($companyId);
        $undepositedLines = $this->undepositedFundsLines($companyId);

        return view('accounting.banking.deposits', compact('bankAccounts', 'undepositedBalance', 'undepositedLines'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $undepositedLines = $this->undepositedFundsLines($companyId);

        return view('accounting.banking.deposit-form', compact('bankAccounts', 'undepositedLines'));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'deposits.create');
        $companyId = (int) session('current_company_id');

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
            $this->createDeposit($validated, auth()->id());

            return redirect()->route('accounting.banking.register', $validated['bank_account_id'])
                ->with('success', 'Deposit recorded successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function undepositedFundsLines(int $companyId): \Illuminate\Support\Collection
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');

        if (! $undepositedAccount) {
            return collect();
        }

        $depositedJeIds = [];

        foreach (BankTransaction::where('company_id', $companyId)
            ->where('source_type', 'make_deposit')
            ->whereNotNull('reference')
            ->get() as $tx) {
            $decoded = json_decode($tx->reference, true);
            if (is_array($decoded)) {
                $depositedJeIds = array_merge($depositedJeIds, $decoded);
            }
        }

        $depositedJeIds = array_unique($depositedJeIds);

        $query = JournalEntryLine::where('account_id', $undepositedAccount->id)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['posted']);
            })
            ->where('debit', '>', 0);

        if (! empty($depositedJeIds)) {
            $query->whereNotIn('journal_entry_id', $depositedJeIds);
        }

        return $query->with('journalEntry')->orderBy('created_at', 'asc')->get();
    }

    protected function undepositedFundsBalance(int $companyId): float
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');

        return $undepositedAccount ? (float) $undepositedAccount->current_balance : 0.0;
    }

    protected function createDeposit(array $data, int $userId): BankTransaction
    {
        foreach (['company_id', 'bank_account_id', 'date', 'amount', 'journal_entry_ids'] as $field) {
            if (! isset($data[$field]) || (is_array($data[$field]) && empty($data[$field]))) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $bankAccountId = $data['bank_account_id'];
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (! $bankAccount) {
            throw new InvalidArgumentException("Bank account ID {$bankAccountId} not found or is not a bank account.");
        }

        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');

        if (! $undepositedAccount) {
            throw new InvalidArgumentException('Undeposited Funds account not found.');
        }

        $selectedJEs = JournalEntryLine::whereIn('journal_entry_id', $data['journal_entry_ids'])
            ->where('account_id', $undepositedAccount->id)
            ->where('debit', '>', 0)
            ->get();

        $totalSelected = $selectedJEs->sum('debit');

        if (round($totalSelected, 2) !== round($amount, 2)) {
            throw new InvalidArgumentException(
                'Selected amount (' . number_format($totalSelected, 2) .
                ') does not match deposit amount (' . number_format($amount, 2) . ').'
            );
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $bankAccountId, $amount, $bankAccount, $undepositedAccount) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'make_deposit',
                'reference' => $data['reference'] ?? null,
                'memo' => $data['description'] ?? "Deposit to {$bankAccount->name}",
                'lines' => [
                    ['account_id' => $bankAccountId, 'debit' => $amount, 'credit' => 0, 'memo' => $data['description'] ?? "Deposit to {$bankAccount->name}"],
                    ['account_id' => $undepositedAccount->id, 'debit' => 0, 'credit' => $amount, 'memo' => $data['description'] ?? "Deposit to {$bankAccount->name}"],
                ],
            ]);

            return BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'deposit',
                'source_type' => 'make_deposit',
                'source_id' => $journalEntry->id,
                'date' => $data['date'],
                'description' => $data['description'] ?? "Deposit to {$bankAccount->name}",
                'reference' => json_encode($data['journal_entry_ids']),
                'amount' => $amount,
                'created_by' => $userId,
            ]);
        });
    }
}
