<?php

namespace App\Services\BankReconciliation;

use App\Models\Account;
use App\Models\DefaultAccountMapping;
use App\Models\Reconciliation;
use App\Models\ReconciliationAdjustment;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdjustmentEngine
{
    /** Adjustment types that post a journal entry to the books. */
    public const POSTING_TYPES = [
        ReconciliationAdjustment::TYPE_BANK_FEES,
        ReconciliationAdjustment::TYPE_INTEREST_EARNED,
        ReconciliationAdjustment::TYPE_BOOK_ERROR,
        ReconciliationAdjustment::TYPE_FOREIGN_EXCHANGE,
        ReconciliationAdjustment::TYPE_MISSING_TRANSACTION,
        ReconciliationAdjustment::TYPE_DUPLICATE,
        ReconciliationAdjustment::TYPE_REVERSAL,
    ];

    /** Adjustment types that are bank-side timing classifications (no JE). */
    public const BANK_SIDE_TYPES = [
        ReconciliationAdjustment::TYPE_UNCLEARED_CHEQUE,
        ReconciliationAdjustment::TYPE_DEPOSIT_IN_TRANSIT,
        ReconciliationAdjustment::TYPE_BANK_ERROR,
    ];

    /** Default side per adjustment type. */
    public const DEFAULT_SIDE = [
        ReconciliationAdjustment::TYPE_UNCLEARED_CHEQUE => ReconciliationAdjustment::SIDE_BANK,
        ReconciliationAdjustment::TYPE_DEPOSIT_IN_TRANSIT => ReconciliationAdjustment::SIDE_BANK,
        ReconciliationAdjustment::TYPE_BANK_ERROR => ReconciliationAdjustment::SIDE_BANK,
        ReconciliationAdjustment::TYPE_BANK_FEES => ReconciliationAdjustment::SIDE_BOOK,
        ReconciliationAdjustment::TYPE_INTEREST_EARNED => ReconciliationAdjustment::SIDE_BOOK,
        ReconciliationAdjustment::TYPE_BOOK_ERROR => ReconciliationAdjustment::SIDE_BOOK,
        ReconciliationAdjustment::TYPE_FOREIGN_EXCHANGE => ReconciliationAdjustment::SIDE_BOOK,
        ReconciliationAdjustment::TYPE_MISSING_TRANSACTION => ReconciliationAdjustment::SIDE_BOOK,
        ReconciliationAdjustment::TYPE_DUPLICATE => ReconciliationAdjustment::SIDE_BOOK,
        ReconciliationAdjustment::TYPE_REVERSAL => ReconciliationAdjustment::SIDE_BOOK,
    ];

    public function __construct(
        private JournalPostingEngine $postingEngine,
        private CalculationService $calculationService,
        private AuditTrailService $auditTrail,
    ) {
    }

    public function create(
        Reconciliation $reconciliation,
        string $type,
        string $sign,
        float $amount,
        ?string $side = null,
        ?int $accountId = null,
        ?string $description = null,
        ?int $userId = null
    ): ReconciliationAdjustment {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be adjusted.');
        }

        if (!in_array($type, ReconciliationAdjustment::TYPES, true)) {
            throw new InvalidArgumentException("Unknown adjustment type: {$type}");
        }

        if (!in_array($sign, [ReconciliationAdjustment::SIGN_ADD, ReconciliationAdjustment::SIGN_SUBTRACT], true)) {
            throw new InvalidArgumentException("Invalid adjustment sign: {$sign}");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Adjustment amount must be greater than zero.');
        }

        $side = $side ?? (self::DEFAULT_SIDE[$type] ?? ReconciliationAdjustment::SIDE_BOOK);
        $accountId = $this->resolveAccountId($reconciliation, $type, $side, $accountId);
        $createdBy = $userId ?? $reconciliation->created_by;

        $adjustment = DB::transaction(function () use ($reconciliation, $type, $side, $sign, $amount, $accountId, $description, $createdBy) {
            $adjustment = ReconciliationAdjustment::create([
                'company_id' => $reconciliation->company_id,
                'reconciliation_id' => $reconciliation->id,
                'type' => $type,
                'side' => $side,
                'sign' => $sign,
                'amount' => $amount,
                'account_id' => $accountId,
                'description' => $description,
                'status' => ReconciliationAdjustment::STATUS_PENDING,
                'created_by' => $createdBy,
            ]);

            if (in_array($type, self::POSTING_TYPES, true)) {
                $journalEntry = $this->postJournalEntry($reconciliation, $adjustment, $createdBy);
                $adjustment->journal_entry_id = $journalEntry->id;
                $adjustment->status = ReconciliationAdjustment::STATUS_POSTED;
                $adjustment->save();
            } else {
                $adjustment->status = ReconciliationAdjustment::STATUS_POSTED;
                $adjustment->save();
            }

            return $adjustment;
        });

        $this->calculationService->recalculate($reconciliation);

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_ADJUSTMENT_ADDED, $createdBy, [
            'type' => $type,
            'side' => $side,
            'sign' => $sign,
            'amount' => $amount,
            'adjustment_id' => $adjustment->id,
        ]);

        return $adjustment;
    }

    public function remove(Reconciliation $reconciliation, int $adjustmentId, int $userId): void
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be adjusted.');
        }

        $adjustment = $reconciliation->adjustments()->findOrFail($adjustmentId);
        $adjustment->delete();

        $this->calculationService->recalculate($reconciliation);

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_ADJUSTMENT_REMOVED, $userId, [
            'adjustment_id' => $adjustmentId,
        ]);
    }

    protected function postJournalEntry(
        Reconciliation $reconciliation,
        ReconciliationAdjustment $adjustment,
        int $userId
    ): \App\Models\JournalEntry {
        $bankAccountId = $reconciliation->bank_account_id;
        $amount = (float) $adjustment->amount;
        $addsToBalance = $adjustment->sign === ReconciliationAdjustment::SIGN_ADD;
        $memo = 'Reconciliation ' . ($reconciliation->statement_number ?: '#' . $reconciliation->id)
            . ' — ' . ReconciliationAdjustment::typeLabel($adjustment->type)
            . ($adjustment->description ? ' — ' . $adjustment->description : '');

        $bankLine = [
            'account_id' => $bankAccountId,
            'debit' => $addsToBalance ? $amount : 0,
            'credit' => $addsToBalance ? 0 : $amount,
            'memo' => $memo,
        ];

        $counterpart = [
            'account_id' => $adjustment->account_id,
            'debit' => $addsToBalance ? 0 : $amount,
            'credit' => $addsToBalance ? $amount : 0,
            'memo' => $memo,
        ];

        return $this->postingEngine->post([
            'company_id' => $reconciliation->company_id,
            'branch_id' => $reconciliation->branch_id,
            'created_by' => $userId,
            'date' => (string) ($reconciliation->period_end ?? $reconciliation->statement_date),
            'source_module' => 'bank_reconciliation',
            'memo' => $memo,
            'is_adjusting_entry' => true,
            'lines' => [$bankLine, $counterpart],
        ]);
    }

    protected function resolveAccountId(
        Reconciliation $reconciliation,
        string $type,
        string $side,
        ?int $requestedAccountId
    ): ?int {
        if ($requestedAccountId !== null) {
            $account = Account::where('id', $requestedAccountId)
                ->where('company_id', $reconciliation->company_id)
                ->first();

            if (!$account) {
                throw new InvalidArgumentException("Account {$requestedAccountId} not found for this company.");
            }

            return (int) $requestedAccountId;
        }

        if ($side === ReconciliationAdjustment::SIDE_BANK) {
            return null;
        }

        $mappingKey = match ($type) {
            ReconciliationAdjustment::TYPE_BANK_FEES => 'merchant_fee_expense',
            ReconciliationAdjustment::TYPE_INTEREST_EARNED => 'default_revenue',
            ReconciliationAdjustment::TYPE_FOREIGN_EXCHANGE => 'realized_fx_gain_loss',
            ReconciliationAdjustment::TYPE_DUPLICATE, ReconciliationAdjustment::TYPE_REVERSAL,
            ReconciliationAdjustment::TYPE_MISSING_TRANSACTION, ReconciliationAdjustment::TYPE_BOOK_ERROR => 'suspense',
            default => 'default_expense',
        };

        return DefaultAccountMapping::getAccountId($reconciliation->company_id, $mappingKey)
            ?? DefaultAccountMapping::getAccountId($reconciliation->company_id, 'default_expense');
    }
}
