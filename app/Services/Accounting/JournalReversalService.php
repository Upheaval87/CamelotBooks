<?php

namespace App\Services\Accounting;

use App\Models\AccountAuditLog;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JournalReversalService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
    ) {
    }

    public static function identityVerifyThreshold(): ?float
    {
        $threshold = config('journal_reversal.identity_verify_threshold');

        if ($threshold === null || $threshold === '') {
            return null;
        }

        return (float) $threshold;
    }

    public function createDraft(JournalEntry $original, int $userId, array $input): JournalEntry
    {
        $this->assertReversible($original);

        $data = $this->buildReversalData($original, $userId, $input);

        $draft = $this->postingEngine->postAsDraft($data);

        $this->logAudit($original, 'reversal_draft_created', null, [
            'draft_entry_id' => $draft->id,
            'draft_journal_number' => $draft->journal_number,
            'reference' => $draft->reference,
        ], $userId, $draft->memo);

        return $draft;
    }

    public function createAndPost(JournalEntry $original, int $userId, array $input): JournalEntry
    {
        $this->assertReversible($original);

        $data = $this->buildReversalData($original, $userId, $input);

        return DB::transaction(function () use ($data, $original, $userId) {
            $draft = $this->postingEngine->postAsDraft($data);

            $draft = $this->postingEngine->postReversalDraft($draft, $userId);

            $this->logAudit($original, 'reversed', [
                'status' => JournalEntry::STATUS_POSTED,
                'reversal_entry_id' => null,
            ], [
                'status' => JournalEntry::STATUS_REVERSED,
                'reversal_entry_id' => $draft->id,
            ], $userId, $draft->memo);

            return $draft;
        });
    }

    public function postDraft(JournalEntry $draft, int $userId): JournalEntry
    {
        $this->assertIsDraftReversal($draft);

        return DB::transaction(function () use ($draft, $userId) {
            $original = $draft->linkedEntry;

            $draft = $this->postingEngine->postReversalDraft($draft, $userId);

            if ($original) {
                $this->logAudit($original, 'reversed', [
                    'status' => JournalEntry::STATUS_POSTED,
                    'reversal_entry_id' => null,
                ], [
                    'status' => JournalEntry::STATUS_REVERSED,
                    'reversal_entry_id' => $draft->id,
                ], $userId, $draft->memo);
            }

            return $draft;
        });
    }

    public function discardDraft(JournalEntry $draft, int $userId): void
    {
        $this->assertIsDraftReversal($draft);

        DB::transaction(function () use ($draft, $userId) {
            $original = $draft->linkedEntry;

            $draft->lines()->delete();
            $draft->delete();

            if ($original && $original->status === JournalEntry::STATUS_POSTED) {
                $this->logAudit($original, 'reversal_draft_discarded', [
                    'draft_entry_id' => $draft->id,
                    'draft_journal_number' => $draft->journal_number,
                ], null, $userId, $draft->memo);
            }
        });
    }

    public function assertReversible(JournalEntry $original): void
    {
        if ($original->status !== JournalEntry::STATUS_POSTED) {
            throw new InvalidArgumentException('Only posted journal entries can be reversed.');
        }

        $existing = JournalEntry::where('linked_entry_id', $original->id)
            ->where(function ($q) {
                $q->where('status', JournalEntry::STATUS_DRAFT)
                    ->orWhere('status', JournalEntry::STATUS_POSTED)
                    ->orWhere('status', JournalEntry::STATUS_PENDING_APPROVAL);
            })
            ->exists();

        if ($existing) {
            throw new InvalidArgumentException(
                'This journal entry already has a reversal. Resolve it before creating another.'
            );
        }
    }

    public function assertIsDraftReversal(JournalEntry $draft): void
    {
        if ($draft->source_module !== 'reversal') {
            throw new InvalidArgumentException('Only reversal entries can be posted or discarded here.');
        }

        if ($draft->status !== JournalEntry::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft reversal entries can be posted or discarded.');
        }
    }

    protected function buildReversalData(JournalEntry $original, int $userId, array $input): array
    {
        $reversalDate = $input['reversal_date'] ?? $original->date->format('Y-m-d');
        $reference = $input['reference'] ?? null;
        $memo = $input['memo'] ?? null;

        $reversedLines = $original->lines->map(function ($line) use ($original) {
            $lineMemo = $line->memo
                ? 'Reversal of ' . $original->journal_number . ' — ' . $line->memo
                : 'Reversal of ' . $original->journal_number;

            return [
                'account_id' => $line->account_id,
                'branch_id' => $line->branch_id,
                'cost_center_id' => $line->cost_center_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'memo' => $lineMemo,
                'entity_type' => $line->entity_type,
                'entity_id' => $line->entity_id,
            ];
        })->toArray();

        return [
            'company_id' => $original->company_id,
            'created_by' => $userId,
            'date' => $reversalDate,
            'reference' => $reference,
            'memo' => $memo,
            'branch_id' => $original->branch_id,
            'is_adjusting_entry' => false,
            'source_module' => 'reversal',
            'linked_entry_id' => $original->id,
            'skip_inactive_account_check' => true,
            'lines' => $reversedLines,
        ];
    }

    protected function logAudit(
        JournalEntry $entry,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        int $userId,
        ?string $notes = null,
    ): void {
        AccountAuditLog::create([
            'company_id' => $entry->company_id,
            'journalable_type' => JournalEntry::class,
            'journalable_id' => $entry->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}