<?php

namespace App\Services\Accounting;

use App\Models\AuditLog;
use App\Models\BankDeposit;
use App\Models\BankDepositLine;
use App\Models\BankTransaction;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntryLine;
use App\Models\SalesReceipt;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankingDepositService
{
    public function __construct(
        protected JournalPostingEngine $postingEngine,
        protected NumberingSequenceService $numbering,
    ) {
    }

    /**
     * The 1050-debit undeposited clearing lines that are not yet claimed by a non-void deposit.
     */
    public function undepositedLines(int $companyId): Collection
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
        if (! $undepositedAccount) {
            return collect();
        }

        $claimedLineIds = BankDepositLine::whereExists(function ($q) {
            $q->selectRaw(1)
                ->from('bank_deposits')
                ->whereColumn('bank_deposits.id', 'bank_deposit_lines.deposit_id')
                ->where('bank_deposits.status', '!=', BankDeposit::STATUS_VOID);
        })->pluck('source_id');

        $query = JournalEntryLine::where('account_id', $undepositedAccount->id)
            ->where('debit', '>', 0)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('status', \App\Models\JournalEntry::STATUS_POSTED);
            })
            ->with('journalEntry');

        if ($claimedLineIds->isNotEmpty()) {
            $query->whereNotIn('id', $claimedLineIds);
        }

        return $query->orderBy('created_at', 'asc')->get()
            ->map(function (JournalEntryLine $line) use ($companyId) {
                $receipt = $this->resolveOwningReceipt($line);
                $paymentMethod = $receipt?->payments?->first()?->paymentMethod?->name;
                $paymentMethod = $this->displayPaymentMethod($paymentMethod);
                return [
                    'line_id' => $line->id,
                    'journal_entry_id' => $line->journal_entry_id,
                    'date' => $line->journalEntry?->date,
                    'reference' => $line->journalEntry?->reference,
                    'memo' => $line->journalEntry?->memo ?? $line->memo,
                    'amount' => (float) $line->debit,
                    'sales_receipt_id' => $receipt?->id,
                    'receipt_number' => $receipt?->receipt_number,
                    'payment_method' => $paymentMethod ?? '—',
                ];
            });
    }

    public function undepositedBalance(int $companyId): float
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
        return $undepositedAccount ? (float) $undepositedAccount->current_balance : 0.0;
    }

    public function getDepositByLine(int $companyId, int $lineId): ?BankDeposit
    {
        return BankDeposit::forCompany($companyId)
            ->whereHas('lines', fn ($q) => $q->where('source_id', $lineId))
            ->where('status', '!=', BankDeposit::STATUS_VOID)
            ->first();
    }

    /**
     * Create a deposit (draft or posted) from a set of 1050 line ids.
     */
    public function create(int $companyId, int $bankAccountId, string $date, array $lineIds, int $userId, array $opts = []): BankDeposit
    {
        $lines = $this->resolveLines($companyId, $bankAccountId, $lineIds);
        $total = $lines->sum('amount');

        if (round($total, 2) <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }

        $post = (bool) ($opts['post'] ?? false);

        return DB::transaction(function () use ($companyId, $bankAccountId, $date, $lines, $userId, $opts, $total, $post) {
            $deposit = BankDeposit::create([
                'company_id' => $companyId,
                'deposit_no' => $this->nextDepositNo($companyId),
                'deposit_date' => $date,
                'bank_account_id' => $bankAccountId,
                'reference' => $opts['reference'] ?? null,
                'description' => $opts['description'] ?? null,
                'total' => $total,
                'status' => BankDeposit::STATUS_DRAFT,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                BankDepositLine::create([
                    'deposit_id' => $deposit->id,
                    'source_type' => BankDepositLine::SOURCE_TYPE_RECEIPT,
                    'source_id' => $line['line_id'],
                    'sales_receipt_id' => $line['sales_receipt_id'] ?? null,
                    'reference' => $line['reference'] ?? null,
                    'description' => $line['memo'] ?? null,
                    'amount' => $line['amount'],
                ]);
            }

            if ($post) {
                $this->post($deposit, $userId);
                $deposit->refresh();
            }

            AuditLog::log(
                $companyId,
                $userId,
                BankDeposit::class,
                $deposit->id,
                $post ? 'deposit.created_posted' : 'deposit.created_draft',
                null,
                ['deposit_no' => $deposit->deposit_no, 'total' => $total, 'bank_account_id' => $bankAccountId],
                $deposit->description ?? "Deposit {$deposit->deposit_no}",
            );

            return $deposit;
        });
    }

    /**
     * Post a draft deposit: build the Dr bank / Cr Undeposited Funds journal, a BankTransaction,
     * and stamp sales_receipts.deposit_id. Returns the deposit (posted).
     */
    public function post(BankDeposit $deposit, int $userId): BankDeposit
    {
        if ($deposit->isPosted()) {
            return $deposit;
        }
        if ($deposit->isVoid()) {
            throw new InvalidArgumentException('A voided deposit cannot be posted.');
        }

        $companyId = (int) $deposit->company_id;
        $amount = (float) $deposit->total;

        // Re-verify the lines are still undeposited (double-post guard).
        $claimed = BankDeposit::forCompany($companyId)
            ->where('id', '!=', $deposit->id)
            ->where('status', '!=', BankDeposit::STATUS_VOID)
            ->whereHas('lines', function ($q) use ($deposit) {
                $q->whereIn('source_id', $deposit->lines()->pluck('source_id'));
            })
            ->exists();
        if ($claimed) {
            throw new InvalidArgumentException('One or more selected receipts were already deposited.');
        }

        return DB::transaction(function () use ($deposit, $userId, $companyId, $amount) {
            $bankAccount = $deposit->bankAccount;
            if (! $bankAccount || ! $bankAccount->is_bank_account) {
                throw new InvalidArgumentException('The deposit target is not a bank account.');
            }

            $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
            if (! $undepositedAccount) {
                throw new InvalidArgumentException('Undeposited Funds account not found.');
            }

            $depositDate = $deposit->deposit_date->format('Y-m-d');

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $depositDate,
                'source_module' => 'make_deposit',
                'reference' => $deposit->deposit_no,
                'memo' => $deposit->description ?? "Deposit {$deposit->deposit_no} to {$bankAccount->name}",
                'lines' => [
                    ['account_id' => $bankAccount->id, 'debit' => $amount, 'credit' => 0, 'memo' => "Deposit {$deposit->deposit_no}"],
                    ['account_id' => $undepositedAccount->id, 'debit' => 0, 'credit' => $amount, 'memo' => "Deposit {$deposit->deposit_no}"],
                ],
            ]);

            $deposit->update([
                'status' => BankDeposit::STATUS_POSTED,
                'posted_by' => $userId,
                'posted_at' => now(),
                'journal_entry_id' => $journalEntry->id,
            ]);

            // Durable BankTransaction for register/reconciliation (replaces the legacy JSON-in-reference).
            BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccount->id,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'deposit',
                'source_type' => 'deposit',
                'source_id' => $deposit->id,
                'date' => $depositDate,
                'description' => $deposit->description ?? "Deposit {$deposit->deposit_no}",
                'reference' => $deposit->deposit_no,
                'amount' => $amount,
                'created_by' => $userId,
            ]);

            // Stamp sales_receipts.deposit_id where a line resolves to a single receipt.
            foreach ($deposit->lines as $line) {
                if ($line->sales_receipt_id) {
                    SalesReceipt::where('id', $line->sales_receipt_id)
                        ->where('company_id', $companyId)
                        ->update(['deposit_id' => $deposit->id]);
                }
            }

            AuditLog::log(
                $companyId,
                $userId,
                BankDeposit::class,
                $deposit->id,
                'deposit.posted',
                null,
                ['journal_entry_id' => $journalEntry->id, 'journal_number' => $journalEntry->journal_number],
                "Posted deposit {$deposit->deposit_no}",
            );

            return $deposit->refresh();
        });
    }

    /**
     * Void a posted deposit: reverse the journal, release receipts (deposit_id -> null).
     */
    public function void(BankDeposit $deposit, int $userId, ?string $reason = null): BankDeposit
    {
        if (! $deposit->isPosted()) {
            throw new InvalidArgumentException('Only a posted deposit can be voided.');
        }

        $companyId = (int) $deposit->company_id;

        return DB::transaction(function () use ($deposit, $userId, $reason, $companyId) {
            if (! $deposit->journal_entry_id) {
                throw new InvalidArgumentException('This deposit has no posted journal entry to reverse.');
            }
            $reversal = $this->postingEngine->reverse($deposit->journal_entry_id, $userId);

            foreach ($deposit->lines as $line) {
                if ($line->sales_receipt_id) {
                    SalesReceipt::where('id', $line->sales_receipt_id)
                        ->where('company_id', $companyId)
                        ->where('deposit_id', $deposit->id)
                        ->update(['deposit_id' => null]);
                }
            }

            $deposit->update([
                'status' => BankDeposit::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
                'journal_entry_id' => $reversal->id,
            ]);

            AuditLog::log(
                $companyId,
                $userId,
                BankDeposit::class,
                $deposit->id,
                'deposit.voided',
                ['status' => BankDeposit::STATUS_POSTED],
                ['status' => BankDeposit::STATUS_VOID, 'reason' => $reason],
                "Voided deposit {$deposit->deposit_no}" . ($reason ? ": {$reason}" : ''),
            );

            return $deposit->refresh();
        });
    }

    protected function resolveLines(int $companyId, int $bankAccountId, array $lineIds): Collection
    {
        if (empty($lineIds)) {
            throw new InvalidArgumentException('Select at least one undeposited receipt to deposit.');
        }

        $bankAccount = \App\Models\Account::where('company_id', $companyId)
            ->where('id', $bankAccountId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->first();
        if (! $bankAccount) {
            throw new InvalidArgumentException('Choose a valid active bank account.');
        }

        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
        if (! $undepositedAccount) {
            throw new InvalidArgumentException('Undeposited Funds account not found.');
        }

        $undeposited = $this->undepositedLines($companyId)->keyBy('line_id');
        $lines = collect();

        foreach (array_unique(array_map('intval', $lineIds)) as $lineId) {
            $candidate = $undeposited->get($lineId);
            if (! $candidate) {
                throw new InvalidArgumentException("Receipt #{$lineId} is not available (already deposited or invalid).");
            }
            $lines->push($candidate);
        }

        return $lines;
    }

    protected function resolveOwningReceipt(JournalEntryLine $line): ?SalesReceipt
    {
        if (! $line->journal_entry_id) {
            return null;
        }
        return SalesReceipt::with('payments.paymentMethod')
            ->where('journal_entry_id', $line->journal_entry_id)
            ->first();
    }

    protected function nextDepositNo(int $companyId): string
    {
        return $this->numbering->getNextNumber($companyId, 'deposit');
    }

    /**
     * Normalise a payment-method name for display. The seeder names cleared-to-
     * undeposited methods "… (Un-deposited)" (e.g. "Bank Transfer (Un-deposited)");
     * those are stripped so the Deposits UI reads just "Bank Transfer".
     */
    protected function displayPaymentMethod(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }
        return trim(preg_replace('/\s*\(un-deposited\)\s*$/i', '', $name));
    }
}
