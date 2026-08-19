<?php

namespace App\Services\Accounting;

use App\Models\RecurringJournalTemplate;
use App\Models\RecurringJournalTemplateLine;
use App\Models\RecurringJournalRun;
use App\Models\RecurringJournalHistory;
use App\Models\RecurringJournalSetting;
use App\Models\JournalEntry;
use App\Models\AccountingPeriod;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecurringJournalService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private NumberingSequenceService $numberingService,
    ) {
    }

    public function createTemplate(array $data, int $companyId, int $userId): RecurringJournalTemplate
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $totalAmount = $this->calculateTotalAmount($data['lines'] ?? []);

            $template = RecurringJournalTemplate::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'memo' => $data['memo'] ?? null,
                'journal_type' => $data['journal_type'] ?? RecurringJournalTemplate::TYPE_STANDARD,
                'currency' => $data['currency'] ?? 'MWK',
                'frequency' => $data['frequency'],
                'day_of_month' => $data['day_of_month'] ?? null,
                'day_of_week' => $data['day_of_week'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'next_run_date' => $data['start_date'],
                'occurrences' => $data['occurrences'] ?? null,
                'generation_mode' => $data['generation_mode'] ?? RecurringJournalTemplate::MODE_AUTO_POST,
                'email_notification' => $data['email_notification'] ?? 'none',
                'auto_post' => ($data['generation_mode'] ?? '') === RecurringJournalTemplate::MODE_AUTO_POST,
                'is_active' => true,
                'status' => RecurringJournalTemplate::STATUS_ACTIVE,
                'total_amount' => $totalAmount,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $line) {
                RecurringJournalTemplateLine::create([
                    'rjt_id' => $template->id,
                    'company_id' => $companyId,
                    'account_id' => $line['account_id'],
                    'branch_id' => $line['branch_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            $template->update(['reference' => $template->reference ?: $this->generateTemplateReference($companyId)]);

            $this->logHistory($companyId, $template->id, null, 'created', "Created recurring journal <b>{$template->name}</b>", 'user', $userId);

            return $template;
        });
    }

    public function updateTemplate(RecurringJournalTemplate $template, array $data, int $userId): RecurringJournalTemplate
    {
        return DB::transaction(function () use ($template, $data, $userId) {
            $totalAmount = $this->calculateTotalAmount($data['lines'] ?? []);

            $template->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? $template->description,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'memo' => $data['memo'] ?? null,
                'journal_type' => $data['journal_type'] ?? $template->journal_type,
                'currency' => $data['currency'] ?? $template->currency,
                'frequency' => $data['frequency'],
                'day_of_month' => $data['day_of_month'] ?? null,
                'day_of_week' => $data['day_of_week'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'occurrences' => $data['occurrences'] ?? null,
                'generation_mode' => $data['generation_mode'] ?? $template->generation_mode,
                'email_notification' => $data['email_notification'] ?? $template->email_notification,
                'auto_post' => ($data['generation_mode'] ?? '') === RecurringJournalTemplate::MODE_AUTO_POST,
                'total_amount' => $totalAmount,
            ]);

            $template->templateLines()->delete();
            foreach ($data['lines'] as $line) {
                RecurringJournalTemplateLine::create([
                    'rjt_id' => $template->id,
                    'company_id' => $template->company_id,
                    'account_id' => $line['account_id'],
                    'branch_id' => $line['branch_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            $this->logHistory($template->company_id, $template->id, null, 'modified', "Modified recurring journal <b>{$template->name}</b>", 'user', $userId);

            return $template->fresh(['templateLines.account', 'branch']);
        });
    }

    public function generateJournal(RecurringJournalTemplate $template, ?int $userId = null, bool $isTest = false): RecurringJournalRun
    {
        $companyId = $template->company_id;

        $run = RecurringJournalRun::create([
            'company_id' => $companyId,
            'recurring_journal_template_id' => $template->id,
            'run_date' => now()->toDateString(),
            'reference' => $this->generateRunReference($companyId),
            'status' => 'draft',
            'total_debit' => $template->total_amount,
            'total_credit' => $template->total_amount,
            'is_test' => $isTest,
            'created_by' => $userId,
        ]);

        if ($isTest) {
            return $run;
        }

        try {
            $period = AccountingPeriod::where('company_id', $companyId)
                ->where('start_date', '<=', now()->toDateString())
                ->where('end_date', '>=', now()->toDateString())
                ->first();

            if ($period && $period->isLocked()) {
                throw new InvalidArgumentException('Period is locked');
            }

            $lines = $template->templateLines->map(fn($line) => [
                'account_id' => $line->account_id,
                'branch_id' => $line->branch_id,
                'cost_center_id' => $line->cost_center_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'memo' => $line->memo,
            ])->toArray();

            $jeData = [
                'company_id' => $companyId,
                'created_by' => $userId ?? $template->created_by,
                'date' => now()->toDateString(),
                'reference' => $run->reference,
                'memo' => $template->memo ?? "Auto-generated from: {$template->name}",
                'source_module' => 'recurring_journal',
                'recurring_template_id' => $template->id,
                'lines' => $lines,
            ];

            $mode = $template->generation_mode;
            $journalEntry = null;

            if ($mode === RecurringJournalTemplate::MODE_AUTO_POST) {
                $journalEntry = $this->postingEngine->post($jeData);
            } elseif ($mode === RecurringJournalTemplate::MODE_DRAFT_ONLY) {
                $journalEntry = $this->postingEngine->postAsDraft($jeData);
            } else {
                $journalEntry = $this->postingEngine->postAsDraft($jeData);
            }

            $run->update([
                'journal_entry_id' => $journalEntry->id,
                'status' => $journalEntry->status,
                'total_debit' => $journalEntry->total_debit,
                'total_credit' => $journalEntry->total_credit,
            ]);

            $template->update([
                'last_generated_at' => now(),
                'generated_count' => $template->generated_count + 1,
            ]);

            $this->advanceNextRunDate($template);

            $action = $mode === RecurringJournalTemplate::MODE_AUTO_POST ? 'auto_posted' : 'generated';
            $this->logHistory($companyId, $template->id, $run->id, $action,
                "Generated journal <b>{$journalEntry->journal_number}</b> from <b>{$template->name}</b>",
                $userId ? 'user' : 'engine', $userId);

            if ($template->occurrences !== null) {
                $remaining = $template->occurrences - 1;
                if ($remaining <= 0) {
                    $template->update(['status' => RecurringJournalTemplate::STATUS_EXPIRED, 'occurrences' => 0]);
                } else {
                    $template->update(['occurrences' => $remaining]);
                }
            }

            return $run;
        } catch (\Throwable $e) {
            $run->update([
                'status' => RecurringJournalRun::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            $template->increment('failed_count');

            $this->logHistory($companyId, $template->id, $run->id, 'failed',
                "Failed to generate journal from <b>{$template->name}</b>: {$e->getMessage()}",
                'engine', null);

            return $run;
        }
    }

    public function runDueSchedules(): array
    {
        $dueTemplates = RecurringJournalTemplate::active()->due()->get();
        $results = ['generated' => 0, 'failed' => 0];

        foreach ($dueTemplates as $template) {
            $run = $this->generateJournal($template, null, false);
            if ($run->status === RecurringJournalRun::STATUS_FAILED) {
                $results['failed']++;
            } else {
                $results['generated']++;
            }
        }

        return $results;
    }

    public function approveRun(RecurringJournalRun $run, int $userId): RecurringJournalRun
    {
        return DB::transaction(function () use ($run, $userId) {
            if ($run->journal_entry_id) {
                $this->postingEngine->approve($run->journal_entry_id, $userId);
                $run->update(['status' => RecurringJournalRun::STATUS_POSTED]);
            }

            $this->logHistory($run->company_id, $run->recurring_journal_template_id, $run->id,
                'approved', "Approved journal run <b>{$run->reference}</b>", 'user', $userId);

            return $run->fresh();
        });
    }

    public function rejectRun(RecurringJournalRun $run, int $userId, string $reason): RecurringJournalRun
    {
        return DB::transaction(function () use ($run, $userId, $reason) {
            if ($run->journal_entry_id) {
                $this->postingEngine->reject($run->journal_entry_id, $userId, $reason);
                $run->update(['status' => RecurringJournalRun::STATUS_FAILED, 'failure_reason' => $reason]);
            }

            $this->logHistory($run->company_id, $run->recurring_journal_template_id, $run->id,
                'rejected', "Rejected journal run <b>{$run->reference}</b>: {$reason}", 'user', $userId);

            return $run->fresh();
        });
    }

    public function pauseTemplate(RecurringJournalTemplate $template, int $userId): void
    {
        $template->update(['status' => RecurringJournalTemplate::STATUS_PAUSED, 'is_active' => false]);
        $this->logHistory($template->company_id, $template->id, null, 'schedule_changed',
            "Paused recurring journal <b>{$template->name}</b>", 'user', $userId);
    }

    public function resumeTemplate(RecurringJournalTemplate $template, int $userId): void
    {
        $template->update(['status' => RecurringJournalTemplate::STATUS_ACTIVE, 'is_active' => true]);
        $this->logHistory($template->company_id, $template->id, null, 'schedule_changed',
            "Resumed recurring journal <b>{$template->name}</b>", 'user', $userId);
    }

    public function renewTemplate(RecurringJournalTemplate $template, array $data, int $userId): void
    {
        $template->update([
            'status' => RecurringJournalTemplate::STATUS_ACTIVE,
            'is_active' => true,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'next_run_date' => $data['start_date'],
            'occurrences' => $data['occurrences'] ?? null,
        ]);
        $this->logHistory($template->company_id, $template->id, null, 'schedule_changed',
            "Renewed recurring journal <b>{$template->name}</b>", 'user', $userId);
    }

    public function deleteTemplate(RecurringJournalTemplate $template, int $userId): void
    {
        $hasRuns = $template->runs()->where('is_test', false)->exists();
        if ($hasRuns) {
            throw new InvalidArgumentException('Cannot delete a recurring journal with existing generated journals.');
        }

        DB::transaction(function () use ($template, $userId) {
            $this->logHistory($template->company_id, $template->id, null, 'deleted',
                "Deleted recurring journal <b>{$template->name}</b>", 'user', $userId);
            $template->templateLines()->delete();
            $template->delete();
        });
    }

    public function duplicateTemplate(RecurringJournalTemplate $source, int $userId): RecurringJournalTemplate
    {
        return DB::transaction(function () use ($source, $userId) {
            $newTemplate = $source->replicate()->fill([
                'name' => "{$source->name} (Copy)",
                'reference' => null,
                'status' => RecurringJournalTemplate::STATUS_ACTIVE,
                'is_active' => true,
                'generated_count' => 0,
                'failed_count' => 0,
                'last_generated_at' => null,
                'created_by' => $userId,
            ]);
            $newTemplate->save();

            foreach ($source->templateLines as $line) {
                $line->replicate()->fill([
                    'rjt_id' => $newTemplate->id,
                ])->save();
            }

            $newTemplate->update(['reference' => $this->generateTemplateReference($source->company_id)]);

            $this->logHistory($source->company_id, $newTemplate->id, null, 'created',
                "Duplicated from <b>{$source->name}</b> as <b>{$newTemplate->name}</b>", 'user', $userId);

            return $newTemplate;
        });
    }

    public function getDashboardStats(int $companyId): array
    {
        $base = RecurringJournalTemplate::where('company_id', $companyId);

        $total = $base->count();
        $active = (clone $base)->active()->count();
        $paused = (clone $base)->paused()->count();
        $expired = (clone $base)->expired()->count();

        $runs = RecurringJournalRun::where('company_id', $companyId);
        $pendingRuns = (clone $runs)->pending()->count();
        $failedRuns = (clone $runs)->failed()->count();
        $generatedThisMonth = (clone $runs)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $postedThisMonth = (clone $runs)->posted()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $totalPostedAmount = (clone $runs)->posted()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_debit');

        $autoPostCount = (clone $base)->active()->where('generation_mode', RecurringJournalTemplate::MODE_AUTO_POST)->count();

        $nextRun = (clone $base)->active()->where('next_run_date', '>=', now()->toDateString())
            ->where('next_run_date', '<=', now()->addDays(7)->toDateString())->count();
        $nextRunAmount = (clone $base)->active()->where('next_run_date', '>=', now()->toDateString())
            ->where('next_run_date', '<=', now()->addDays(7)->toDateString())->sum('total_amount');

        $upcomingRuns = RecurringJournalTemplate::where('company_id', $companyId)
            ->active()->where('next_run_date', '>=', now()->toDateString())
            ->orderBy('next_run_date')
            ->limit(10)
            ->get();

        $nextActiveRun = RecurringJournalTemplate::where('company_id', $companyId)
            ->active()->where('next_run_date', '>=', now()->toDateString())
            ->orderBy('next_run_date')->first();
        $daysUntilNextRun = $nextActiveRun ? now()->diffInDays($nextActiveRun->next_run_date, false) : null;

        return compact(
            'total', 'active', 'paused', 'expired',
            'pendingRuns', 'failedRuns', 'generatedThisMonth', 'postedThisMonth',
            'totalPostedAmount', 'autoPostCount', 'nextRun', 'nextRunAmount',
            'upcomingRuns', 'daysUntilNextRun'
        );
    }

    private function advanceNextRunDate(RecurringJournalTemplate $template): void
    {
        $next = match ($template->frequency) {
            RecurringJournalTemplate::FREQ_DAILY => now()->addDay()->toDateString(),
            RecurringJournalTemplate::FREQ_WEEKLY => now()->addWeek()->toDateString(),
            RecurringJournalTemplate::FREQ_BIWEEKLY => now()->addWeeks(2)->toDateString(),
            RecurringJournalTemplate::FREQ_MONTHLY => now()->addMonthNoOverflow()->toDateString(),
            RecurringJournalTemplate::FREQ_QUARTERLY => now()->addMonthsNoOverflow(3)->toDateString(),
            RecurringJournalTemplate::FREQ_SEMI_ANNUALLY => now()->addMonthsNoOverflow(6)->toDateString(),
            RecurringJournalTemplate::FREQ_YEARLY => now()->addYearNoOverflow()->toDateString(),
            default => now()->addMonthNoOverflow()->toDateString(),
        };

        if ($template->end_date && Carbon::parse($next)->greaterThan($template->end_date)) {
            $template->update(['status' => RecurringJournalTemplate::STATUS_EXPIRED, 'is_active' => false]);
        } else {
            $template->update(['next_run_date' => $next]);
        }
    }

    private function calculateTotalAmount(array $lines): float
    {
        $total = 0;
        foreach ($lines as $line) {
            $total += (float) ($line['debit'] ?? 0);
        }
        return round($total, 2);
    }

    private function generateTemplateReference(int $companyId): string
    {
        return $this->numberingService->getNextNumber($companyId, 'rj_template');
    }

    private function generateRunReference(int $companyId): string
    {
        return $this->numberingService->getNextNumber($companyId, 'rj_generated');
    }

    private function logHistory(int $companyId, ?int $templateId, ?int $runId, string $action, string $description, string $actorType = 'user', ?int $actorId = null): void
    {
        RecurringJournalHistory::create([
            'company_id' => $companyId,
            'recurring_journal_template_id' => $templateId,
            'recurring_journal_run_id' => $runId,
            'action' => $action,
            'description' => $description,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'happened_at' => now(),
        ]);
    }
}
