<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\AccountingPeriod;
use App\Models\ApprovalSetting;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JournalPostingEngine
{
    public function post(array $data): JournalEntry
    {
        $data['status'] = JournalEntry::STATUS_POSTED;

        return DB::transaction(function () use (&$data) {
            $companyId = $data['company_id'];
            $approvalSetting = ApprovalSetting::where('company_id', $companyId)->first();
            $totalAmount = $this->calculateTotalDebit($data['lines']);

            if ($approvalSetting && $approvalSetting->isApprovalRequired($totalAmount)) {
                $data['status'] = JournalEntry::STATUS_PENDING_APPROVAL;
            }

            $this->validateEntry($data);

            $data['journal_number'] = $this->generateJournalNumber($companyId);

            if ($data['status'] === JournalEntry::STATUS_POSTED) {
                $data['posted_by'] = $data['created_by'];
                $data['posted_at'] = now();
            }

            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'journal_number' => $data['journal_number'],
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => $data['status'],
                'is_adjusting_entry' => $data['is_adjusting_entry'] ?? false,
                'source_module' => $data['source_module'] ?? null,
                'linked_entry_id' => $data['linked_entry_id'] ?? null,
                'created_by' => $data['created_by'],
                'posted_by' => $data['posted_by'] ?? null,
                'posted_at' => $data['posted_at'] ?? null,
            ]);

            $this->createLines($entry, $data['lines']);

            if ($entry->status === JournalEntry::STATUS_POSTED) {
                $this->updateAccountBalances($entry);
            }

            $this->logAction($entry, 'created', null, $entry->toArray(), $data['created_by']);

            if ($entry->status === JournalEntry::STATUS_POSTED) {
                $this->logAction($entry, 'posted', ['status' => JournalEntry::STATUS_DRAFT], ['status' => JournalEntry::STATUS_POSTED], $data['created_by']);
            }

            return $entry;
        });
    }

    public function postAsDraft(array $data): JournalEntry
    {
        $data['status'] = JournalEntry::STATUS_DRAFT;

        return DB::transaction(function () use (&$data) {
            $this->validateEntry($data);

            $companyId = $data['company_id'];
            $data['journal_number'] = $this->generateJournalNumber($companyId);

            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'journal_number' => $data['journal_number'],
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => JournalEntry::STATUS_DRAFT,
                'is_adjusting_entry' => $data['is_adjusting_entry'] ?? false,
                'source_module' => $data['source_module'] ?? null,
                'linked_entry_id' => $data['linked_entry_id'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            $this->createLines($entry, $data['lines']);

            $this->logAction($entry, 'created_draft', null, $entry->toArray(), $data['created_by']);

            return $entry;
        });
    }

    public function submitForApproval(int $journalEntryId): JournalEntry
    {
        return DB::transaction(function () use ($journalEntryId) {
            $entry = JournalEntry::with('lines.account')->findOrFail($journalEntryId);

            if ($entry->status !== JournalEntry::STATUS_DRAFT) {
                throw new InvalidArgumentException('Only draft entries can be submitted for approval.');
            }

            $approvalSetting = ApprovalSetting::where('company_id', $entry->company_id)->first();

            if (!$approvalSetting || !$approvalSetting->requires_approval) {
                $entry->status = JournalEntry::STATUS_POSTED;
                $entry->posted_by = $entry->created_by;
                $entry->posted_at = now();
                $entry->save();

                $this->updateAccountBalances($entry);
                $this->logAction($entry, 'posted', ['status' => JournalEntry::STATUS_DRAFT], ['status' => JournalEntry::STATUS_POSTED], $entry->created_by);

                return $entry;
            }

            $totalAmount = $this->calculateTotalFromLines($entry->lines);

            if (!$approvalSetting->isApprovalRequired($totalAmount)) {
                $entry->status = JournalEntry::STATUS_POSTED;
                $entry->posted_by = $entry->created_by;
                $entry->posted_at = now();
                $entry->save();

                $this->updateAccountBalances($entry);
                $this->logAction($entry, 'posted', ['status' => JournalEntry::STATUS_DRAFT], ['status' => JournalEntry::STATUS_POSTED], $entry->created_by);

                return $entry;
            }

            $oldStatus = $entry->status;
            $entry->status = JournalEntry::STATUS_PENDING_APPROVAL;
            $entry->save();

            $this->logAction($entry, 'submitted_for_approval', ['status' => $oldStatus], ['status' => JournalEntry::STATUS_PENDING_APPROVAL], $entry->created_by);

            return $entry;
        });
    }

    public function approve(int $journalEntryId, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($journalEntryId, $userId) {
            $entry = JournalEntry::with('lines.account')->findOrFail($journalEntryId);

            if ($entry->status !== JournalEntry::STATUS_PENDING_APPROVAL) {
                throw new InvalidArgumentException('Only entries pending approval can be approved.');
            }

            $oldStatus = $entry->status;
            $entry->status = JournalEntry::STATUS_POSTED;
            $entry->approved_by = $userId;
            $entry->approved_at = now();
            $entry->posted_by = $userId;
            $entry->posted_at = now();
            $entry->save();

            $this->updateAccountBalances($entry);

            $this->logAction($entry, 'approved', ['status' => $oldStatus], ['status' => JournalEntry::STATUS_POSTED], $userId);

            return $entry;
        });
    }

    public function reject(int $journalEntryId, int $userId, string $reason): JournalEntry
    {
        return DB::transaction(function () use ($journalEntryId, $userId, $reason) {
            $entry = JournalEntry::findOrFail($journalEntryId);

            if ($entry->status !== JournalEntry::STATUS_PENDING_APPROVAL) {
                throw new InvalidArgumentException('Only entries pending approval can be rejected.');
            }

            $oldStatus = $entry->status;
            $entry->status = JournalEntry::STATUS_DRAFT;
            $entry->rejected_by = $userId;
            $entry->rejected_at = now();
            $entry->rejection_reason = $reason;
            $entry->save();

            $this->logAction($entry, 'rejected', ['status' => $oldStatus], ['status' => JournalEntry::STATUS_DRAFT, 'rejection_reason' => $reason], $userId);

            return $entry;
        });
    }

    public function reverse(int $journalEntryId, int $userId, ?string $date = null): JournalEntry
    {
        return DB::transaction(function () use ($journalEntryId, $userId, $date) {
            $original = JournalEntry::with('lines')->findOrFail($journalEntryId);

            if ($original->status !== JournalEntry::STATUS_POSTED) {
                throw new InvalidArgumentException('Only posted entries can be reversed.');
            }

            $reversalDate = $date ?? $original->date->format('Y-m-d');
            $companyId = $original->company_id;

            $approvalSetting = ApprovalSetting::where('company_id', $companyId)->first();
            $totalAmount = $this->calculateTotalFromLines($original->lines);

            $initialStatus = JournalEntry::STATUS_POSTED;

            if ($approvalSetting && $approvalSetting->isApprovalRequired($totalAmount)) {
                $initialStatus = JournalEntry::STATUS_PENDING_APPROVAL;
            }

            $journalNumber = $this->generateJournalNumber($companyId);

            $reversalEntry = JournalEntry::create([
                'company_id' => $companyId,
                'branch_id' => $original->branch_id,
                'journal_number' => $journalNumber,
                'date' => $reversalDate,
                'reference' => null,
                'memo' => 'Reversal of ' . $original->journal_number,
                'status' => $initialStatus,
                'is_adjusting_entry' => false,
                'source_module' => 'reversal',
                'linked_entry_id' => $original->id,
                'created_by' => $userId,
            ]);

            $reversedLines = $original->lines->map(function ($line) {
                return [
                    'account_id' => $line->account_id,
                    'branch_id' => $line->branch_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'memo' => $line->memo,
                    'entity_type' => $line->entity_type,
                    'entity_id' => $line->entity_id,
                ];
            })->toArray();

            $this->createLines($reversalEntry, $reversedLines);

            if ($initialStatus === JournalEntry::STATUS_POSTED) {
                $reversalEntry->posted_by = $userId;
                $reversalEntry->posted_at = now();
                $reversalEntry->save();

                $this->updateAccountBalances($reversalEntry);
            }

            $original->status = JournalEntry::STATUS_REVERSED;
            $original->save();

            $this->logAction($reversalEntry, 'reversal_created', null, [
                'original_entry_id' => $original->id,
                'original_journal_number' => $original->journal_number,
            ], $userId);

            $this->logAction($original, 'reversed', ['status' => JournalEntry::STATUS_POSTED], ['status' => JournalEntry::STATUS_REVERSED], $userId);

            return $reversalEntry;
        });
    }

    protected function validateEntry(array $data): void
    {
        $lines = $data['lines'] ?? [];

        if (empty($lines)) {
            throw new InvalidArgumentException('At least one journal entry line is required.');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException("Line " . ($index + 1) . ": Debit and credit amounts cannot be negative.");
            }

            if ($debit === 0 && $credit === 0) {
                throw new InvalidArgumentException("Line " . ($index + 1) . ": Either debit or credit must be greater than zero.");
            }

            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException("Line " . ($index + 1) . ": A line cannot have both debit and credit amounts.");
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException(
                "Debits and credits must be equal. Debit: " . number_format($totalDebit, 2) . ", Credit: " . number_format($totalCredit, 2)
            );
        }

        $companyId = $data['company_id'];
        $accountIds = array_column($lines, 'account_id');

        $invalidAccounts = Account::where('company_id', '!=', $companyId)
            ->whereIn('id', $accountIds)
            ->pluck('id')
            ->toArray();

        if (!empty($invalidAccounts)) {
            throw new InvalidArgumentException('One or more accounts do not belong to company ' . $companyId . '.');
        }

        $nonExistentAccounts = Account::whereNotIn('id', $accountIds)->pluck('id')->toArray();

        if (!empty($nonExistentAccounts)) {
            throw new InvalidArgumentException('One or more accounts do not exist: ' . implode(', ', $nonExistentAccounts));
        }

        $entryDate = $data['date'];
        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $entryDate)
            ->where('end_date', '>=', $entryDate)
            ->first();

        if (!$period) {
            throw new InvalidArgumentException('No accounting period found for date ' . $entryDate . '.');
        }

        if ($period->isClosed()) {
            throw new InvalidArgumentException('The accounting period for date ' . $entryDate . ' is closed. No entries can be made.');
        }

        if ($period->isLocked()) {
            throw new InvalidArgumentException('The accounting period for date ' . $entryDate . ' is locked. No entries can be made.');
        }

        $journalNumber = $data['journal_number'] ?? null;
        if ($journalNumber) {
            $exists = JournalEntry::where('company_id', $companyId)
                ->where('journal_number', $journalNumber)
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException('Journal number ' . $journalNumber . ' already exists for this company.');
            }
        }
    }

    protected function generateJournalNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'JE-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastEntry = JournalEntry::where('company_id', $companyId)
            ->where('journal_number', 'like', $prefix . '%')
            ->orderByDesc('journal_number')
            ->first();

        if ($lastEntry) {
            $lastSequence = (int) substr($lastEntry->journal_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function createLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'branch_id' => $line['branch_id'] ?? $entry->branch_id,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'memo' => $line['memo'] ?? null,
                'entity_type' => $line['entity_type'] ?? null,
                'entity_id' => $line['entity_id'] ?? null,
            ]);
        }
    }

    protected function updateAccountBalances(JournalEntry $entry): void
    {
        $entry->load('lines');

        foreach ($entry->lines as $line) {
            $account = Account::where('id', $line->account_id)->lockForUpdate()->first();

            if (!$account) {
                throw new InvalidArgumentException("Account {$line->account_id} not found.");
            }

            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($account->isDebitNormal()) {
                $account->current_balance = (float) $account->current_balance + $debit - $credit;
            } else {
                $account->current_balance = (float) $account->current_balance + $credit - $debit;
            }

            $account->save();
        }
    }

    protected function calculateTotalDebit(array $lines): float
    {
        $total = 0;
        foreach ($lines as $line) {
            $total += (float) ($line['debit'] ?? 0);
        }
        return $total;
    }

    protected function calculateTotalFromLines($lines): float
    {
        $total = 0;
        foreach ($lines as $line) {
            $total += (float) $line->debit;
        }
        return $total;
    }

    protected function logAction(JournalEntry $entry, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $entry->company_id,
            'journalable_type' => JournalEntry::class,
            'journalable_id' => $entry->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
