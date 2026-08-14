<?php

namespace App\Services\BankReconciliation;

use App\Models\ApprovalSetting;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Models\Reconciliation;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationMatch;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReconciliationService
{
    public function __construct(
        private StatementImportParser $parser,
        private MatchingEngine $matchingEngine,
        private AdjustmentEngine $adjustmentEngine,
        private CalculationService $calculationService,
        private AuditTrailService $auditTrail,
    ) {
    }

    public function create(array $data, int $userId): Reconciliation
    {
        $companyId = (int) $data['company_id'];

        $this->guardOverlap(
            $companyId,
            (int) $data['bank_account_id'],
            $data['period_start'] ?? $data['statement_date'] ?? null,
            $data['period_end'] ?? $data['statement_date'] ?? null,
        );

        return DB::transaction(function () use ($data, $companyId, $userId) {
            $reconciliation = Reconciliation::create([
                'company_id' => $companyId,
                'bank_account_id' => (int) $data['bank_account_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'statement_number' => $data['statement_number'] ?? null,
                'statement_date' => $data['statement_date'],
                'period_start' => $data['period_start'] ?? $data['statement_date'] ?? null,
                'period_end' => $data['period_end'] ?? $data['statement_date'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'closing_balance' => $data['closing_balance'] ?? 0,
                'currency' => $data['currency'] ?? 'MWK',
                'status' => Reconciliation::STATUS_DRAFT,
                'statement_balance' => $data['closing_balance'] ?? 0,
                'book_balance' => 0,
                'difference' => 0,
                'created_by' => $userId,
            ]);

            $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_CREATED, $userId, [
                'statement_number' => $reconciliation->statement_number,
            ]);

            return $reconciliation;
        });
    }

    public function update(Reconciliation $reconciliation, array $data, int $userId): Reconciliation
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be edited.');
        }

        $reconciliation->update([
            'statement_number' => $data['statement_number'] ?? $reconciliation->statement_number,
            'statement_date' => $data['statement_date'] ?? $reconciliation->statement_date,
            'period_start' => $data['period_start'] ?? $reconciliation->period_start,
            'period_end' => $data['period_end'] ?? $reconciliation->period_end,
            'opening_balance' => $data['opening_balance'] ?? $reconciliation->opening_balance,
            'closing_balance' => $data['closing_balance'] ?? $reconciliation->closing_balance,
        ]);

        $this->calculationService->recalculate($reconciliation);

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_UPDATED, $userId, [
            'fields' => array_keys($data),
        ]);

        return $reconciliation->fresh();
    }

    public function importStatement(Reconciliation $reconciliation, string $path, string $filename, int $userId): BankStatementImport
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot import statements.');
        }

        $parsed = $this->parser->parse($path, $filename);

        return $this->persistImport($reconciliation, $parsed, $userId);
    }

    public function importStatementWithMapping(Reconciliation $reconciliation, string $path, string $filename, int $userId, array $map, bool $hasHeader = true): BankStatementImport
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot import statements.');
        }

        $parsed = $this->parser->parseWithMapping($path, $filename, $map, $hasHeader);

        return $this->persistImport($reconciliation, $parsed, $userId);
    }

    public function previewStatementFile(string $path, string $filename): array
    {
        return $this->parser->preview($path, $filename);
    }

    public function suggestColumnMapping(array $header): array
    {
        return $this->parser->suggestMapping($header);
    }

    protected function persistImport(Reconciliation $reconciliation, array $parsed, int $userId): BankStatementImport
    {
        return DB::transaction(function () use ($reconciliation, $parsed, $userId) {
            $import = BankStatementImport::create([
                'company_id' => $reconciliation->company_id,
                'bank_account_id' => $reconciliation->bank_account_id,
                'reconciliation_id' => $reconciliation->id,
                'filename' => $parsed['filename'],
                'statement_date' => $reconciliation->statement_date,
                'statement_end_balance' => $reconciliation->closing_balance,
                'line_count' => $parsed['line_count'],
                'imported_by' => $userId,
            ]);

            foreach ($parsed['rows'] as $row) {
                BankStatementLine::create([
                    'import_id' => $import->id,
                    'company_id' => $reconciliation->company_id,
                    'reconciliation_id' => $reconciliation->id,
                    'bank_account_id' => $reconciliation->bank_account_id,
                    'transaction_date' => $row['transaction_date'],
                    'description' => $row['description'],
                    'reference' => $row['reference'],
                    'amount' => $row['amount'],
                    'balance' => $row['balance'],
                    'is_matched' => false,
                    'status' => BankStatementLine::STATUS_UNMATCHED,
                ]);
            }

            $reconciliation->status = Reconciliation::STATUS_IN_PROGRESS;
            $reconciliation->save();

            $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_IMPORTED, $userId, [
                'filename' => $parsed['filename'],
                'line_count' => $parsed['line_count'],
            ]);

            return $import;
        });
    }

    /**
     * Apply auto-match suggestions (exact + likely) for the reconciliation.
     */
    public function applyAutoMatches(Reconciliation $reconciliation, int $userId): array
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be auto-matched.');
        }

        $suggestions = $this->matchingEngine->suggest($reconciliation);
        $applied = 0;

        foreach (['exact', 'likely'] as $bucket) {
            foreach ($suggestions[$bucket] as $suggestion) {
                $this->match(
                    $reconciliation,
                    $suggestion->line->id,
                    $suggestion->transaction->id,
                    'auto',
                    $suggestion->confidence,
                    $userId
                );
                $applied++;
            }
        }

        if ($applied > 0) {
            $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_MATCHED, $userId, [
                'method' => 'auto',
                'count' => $applied,
            ]);
        }

        return ['applied' => $applied];
    }

    /**
     * Match a statement line to a book transaction. Supports one-to-many and
     * many-to-one via repeated calls against the same reconciliation.
     */
    public function match(
        Reconciliation $reconciliation,
        int $statementLineId,
        int $bankTransactionId,
        string $method = 'manual',
        ?float $confidence = null,
        int $userId = 0
    ): ReconciliationMatch {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be matched.');
        }

        $line = BankStatementLine::where('id', $statementLineId)
            ->where('reconciliation_id', $reconciliation->id)
            ->firstOrFail();

        $transaction = BankTransaction::where('id', $bankTransactionId)
            ->where('company_id', $reconciliation->company_id)
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->firstOrFail();

        $existing = ReconciliationMatch::where('reconciliation_id', $reconciliation->id)
            ->where('bank_statement_line_id', $line->id)
            ->where('bank_transaction_id', $transaction->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $match = ReconciliationMatch::create([
            'company_id' => $reconciliation->company_id,
            'reconciliation_id' => $reconciliation->id,
            'bank_statement_line_id' => $line->id,
            'bank_transaction_id' => $transaction->id,
            'method' => $method,
            'confidence' => $confidence,
            'created_by' => $userId ?: $reconciliation->created_by,
        ]);

        $line->is_matched = true;
        $line->status = BankStatementLine::STATUS_MATCHED;
        $line->match_id = $match->id;
        $line->save();

        $transaction->reconciliation_status = BankTransaction::RECON_STATUS_MATCHED;
        $transaction->save();

        if ($method === 'manual') {
            $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_MATCHED, $userId, [
                'statement_line_id' => $line->id,
                'bank_transaction_id' => $transaction->id,
                'method' => 'manual',
            ]);
        }

        return $match;
    }

    public function unmatch(Reconciliation $reconciliation, int $matchId, int $userId): void
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be unmatched.');
        }

        $match = ReconciliationMatch::where('reconciliation_id', $reconciliation->id)
            ->findOrFail($matchId);

        $lineId = $match->bank_statement_line_id;
        $transactionId = $match->bank_transaction_id;
        $match->delete();

        $this->refreshLineStatus($lineId);
        $this->refreshTransactionStatus($transactionId);

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_UNMATCHED, $userId, [
            'statement_line_id' => $lineId,
            'bank_transaction_id' => $transactionId,
        ]);
    }

    public function markReadyForReview(Reconciliation $reconciliation, int $userId): Reconciliation
    {
        $this->assertEditable($reconciliation);
        $this->assertImported($reconciliation);

        $reconciliation->status = Reconciliation::STATUS_READY_FOR_REVIEW;
        $reconciliation->save();

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_READY_FOR_REVIEW, $userId);

        return $reconciliation->fresh();
    }

    public function reopen(Reconciliation $reconciliation, int $userId): Reconciliation
    {
        if (!in_array($reconciliation->status, [Reconciliation::STATUS_READY_FOR_REVIEW, Reconciliation::STATUS_APPROVED], true)) {
            throw new InvalidArgumentException('Only reconciliations pending review or approval can be reopened.');
        }

        $reconciliation->status = Reconciliation::STATUS_IN_PROGRESS;
        $reconciliation->approved_by = null;
        $reconciliation->approved_at = null;
        $reconciliation->save();

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_REOPENED, $userId);

        return $reconciliation->fresh();
    }

    public function approve(Reconciliation $reconciliation, int $userId): Reconciliation
    {
        if (!$this->approvalRequired($reconciliation)) {
            throw new InvalidArgumentException('Approval is not enabled for this company.');
        }

        if ($reconciliation->status !== Reconciliation::STATUS_READY_FOR_REVIEW) {
            throw new InvalidArgumentException('Only reconciliations in review can be approved.');
        }

        $reconciliation->status = Reconciliation::STATUS_APPROVED;
        $reconciliation->approved_by = $userId;
        $reconciliation->approved_at = now();
        $reconciliation->save();

        $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_APPROVED, $userId);

        return $reconciliation->fresh();
    }

    public function complete(Reconciliation $reconciliation, int $userId): Reconciliation
    {
        $this->assertEditable($reconciliation);

        if (!$reconciliation->isBalanced()) {
            throw new InvalidArgumentException(
                'Cannot complete — difference remaining ' . number_format((float) $reconciliation->difference, 2) . '.'
            );
        }

        $approvalRequired = $this->approvalRequired($reconciliation);
        $expectedStatus = $approvalRequired
            ? Reconciliation::STATUS_APPROVED
            : Reconciliation::STATUS_READY_FOR_REVIEW;

        if ($reconciliation->status !== $expectedStatus) {
            $message = $approvalRequired
                ? 'Reconciliation must be approved before completion.'
                : 'Reconciliation must be marked ready for review before completion.';
            throw new InvalidArgumentException($message);
        }

        return DB::transaction(function () use ($reconciliation, $userId) {
            $this->lockLinesAndTransactions($reconciliation);

            $reconciliation->status = Reconciliation::STATUS_RECONCILED;
            $reconciliation->completed_by = $userId;
            $reconciliation->completed_at = now();
            $reconciliation->save();

            $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_COMPLETED, $userId, [
                'difference' => $reconciliation->difference,
            ]);

            return $reconciliation->fresh();
        });
    }

    public function reverse(Reconciliation $reconciliation, string $reason, int $userId): Reconciliation
    {
        if ($reconciliation->status !== Reconciliation::STATUS_RECONCILED) {
            throw new InvalidArgumentException('Only reconciled reconciliations can be reversed.');
        }

        return DB::transaction(function () use ($reconciliation, $reason, $userId) {
            // Unlock the previously reconciled book transactions and lines.
            BankTransaction::where('bank_reconciliation_id', $reconciliation->id)
                ->where('is_reconciled', true)
                ->update([
                    'is_reconciled' => false,
                    'reconciled_at' => null,
                    'reconciliation_status' => BankTransaction::RECON_STATUS_UNMATCHED,
                ]);

            BankStatementLine::where('reconciliation_id', $reconciliation->id)
                ->update([
                    'is_matched' => false,
                    'status' => BankStatementLine::STATUS_UNMATCHED,
                    'match_id' => null,
                ]);

            ReconciliationMatch::where('reconciliation_id', $reconciliation->id)->delete();

            $reconciliation->status = Reconciliation::STATUS_REVERSED;
            $reconciliation->reversed_by = $userId;
            $reconciliation->reversed_at = now();
            $reconciliation->reversal_reason = $reason;
            $reconciliation->save();

            $this->auditTrail->log($reconciliation, ReconciliationAuditLog::ACTION_REVERSED, $userId, [
                'reason' => $reason,
            ]);

            return $reconciliation->fresh();
        });
    }

    public function approvalRequired(Reconciliation $reconciliation): bool
    {
        $setting = ApprovalSetting::where('company_id', $reconciliation->company_id)->first();

        return $setting && (bool) $setting->requires_approval;
    }

    public function adjust(
        Reconciliation $reconciliation,
        string $type,
        string $sign,
        float $amount,
        ?string $side = null,
        ?int $accountId = null,
        ?string $description = null,
        ?int $userId = null
    ) {
        return $this->adjustmentEngine->create(
            $reconciliation,
            $type,
            $sign,
            $amount,
            $side,
            $accountId,
            $description,
            $userId
        );
    }

    public function removeAdjustment(Reconciliation $reconciliation, int $adjustmentId, int $userId): void
    {
        $this->adjustmentEngine->remove($reconciliation, $adjustmentId, $userId);
    }

    public function matchingEngine(): MatchingEngine
    {
        return $this->matchingEngine;
    }

    public function calculationService(): CalculationService
    {
        return $this->calculationService;
    }

    protected function guardOverlap(int $companyId, int $bankAccountId, ?string $periodStart, ?string $periodEnd): void
    {
        $query = Reconciliation::where('company_id', $companyId)
            ->where('bank_account_id', $bankAccountId)
            ->whereIn('status', Reconciliation::ACTIVE_STATUSES);

        if ($periodStart && $periodEnd) {
            $query->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('period_start', [$periodStart, $periodEnd])
                    ->orWhereBetween('period_end', [$periodStart, $periodEnd])
                    ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                        $q2->where('period_start', '<=', $periodStart)
                            ->where('period_end', '>=', $periodEnd);
                    });
            });
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('An open reconciliation already covers this period for the selected account.');
        }
    }

    protected function assertEditable(Reconciliation $reconciliation): void
    {
        if ($reconciliation->isLocked()) {
            throw new InvalidArgumentException('Locked reconciliations cannot be modified.');
        }
    }

    protected function assertImported(Reconciliation $reconciliation): void
    {
        $hasLines = BankStatementLine::where('reconciliation_id', $reconciliation->id)->exists();
        if (!$hasLines) {
            throw new InvalidArgumentException('Import a bank statement before marking the reconciliation ready for review.');
        }
    }

    protected function refreshLineStatus(int $lineId): void
    {
        $line = BankStatementLine::find($lineId);
        if (!$line) {
            return;
        }

        $remaining = ReconciliationMatch::where('bank_statement_line_id', $lineId)->count();

        if ($remaining === 0) {
            $line->is_matched = false;
            $line->status = BankStatementLine::STATUS_UNMATCHED;
            $line->match_id = null;
            $line->save();
        } else {
            $primary = ReconciliationMatch::where('bank_statement_line_id', $lineId)->first();
            $line->match_id = $primary->id;
            $line->save();
        }
    }

    protected function refreshTransactionStatus(int $transactionId): void
    {
        $transaction = BankTransaction::find($transactionId);
        if (!$transaction) {
            return;
        }

        $remaining = ReconciliationMatch::where('bank_transaction_id', $transactionId)->count();
        $transaction->reconciliation_status = $remaining > 0
            ? BankTransaction::RECON_STATUS_MATCHED
            : BankTransaction::RECON_STATUS_UNMATCHED;
        $transaction->save();
    }

    protected function lockLinesAndTransactions(Reconciliation $reconciliation): void
    {
        $matchedTransactions = ReconciliationMatch::where('reconciliation_id', $reconciliation->id)
            ->pluck('bank_transaction_id')
            ->unique()
            ->values();

        BankTransaction::whereIn('id', $matchedTransactions)
            ->update([
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'bank_reconciliation_id' => $reconciliation->id,
                'reconciliation_status' => BankTransaction::RECON_STATUS_RECONCILED,
            ]);

        BankStatementLine::where('reconciliation_id', $reconciliation->id)
            ->where('is_matched', true)
            ->update([
                'status' => BankStatementLine::STATUS_RECONCILED,
            ]);
    }
}
