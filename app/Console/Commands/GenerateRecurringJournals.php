<?php

namespace App\Console\Commands;

use App\Models\RecurringJournalTemplate;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Console\Command;

class GenerateRecurringJournals extends Command
{
    protected $signature = 'journals:generate-recurring';

    protected $description = 'Generate journal entries from active recurring templates';

    public function handle(JournalPostingEngine $postingEngine): int
    {
        $templates = RecurringJournalTemplate::active()->due()->get();

        $count = 0;

        foreach ($templates as $template) {
            $lines = $template->templateLines->map(function ($line) {
                return [
                    'account_id' => $line->account_id,
                    'branch_id' => $line->branch_id,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'memo' => $line->memo,
                ];
            })->toArray();

            $data = [
                'company_id' => $template->company_id,
                'branch_id' => $template->branch_id,
                'created_by' => $template->created_by,
                'date' => $template->next_run_date->toDateString(),
                'memo' => $template->memo,
                'source_module' => 'recurring',
                'lines' => $lines,
            ];

            try {
                if ($template->auto_post) {
                    $entry = $postingEngine->post($data);
                } else {
                    $entry = $postingEngine->postAsDraft($data);
                }

                $entry->update(['recurring_template_id' => $template->id]);

                $template->update([
                    'next_run_date' => $this->calculateNextRunDate($template),
                ]);

                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to generate entry for template '{$template->name}': {$e->getMessage()}");
            }
        }

        $this->info("Generated {$count} journal entries from recurring templates.");

        return Command::SUCCESS;
    }

    private function calculateNextRunDate(RecurringJournalTemplate $template): \Carbon\Carbon
    {
        $current = $template->next_run_date->copy();

        switch ($template->frequency) {
            case RecurringJournalTemplate::FREQ_WEEKLY:
                return $current->addWeek();
            case RecurringJournalTemplate::FREQ_BIWEEKLY:
                return $current->addWeeks(2);
            case RecurringJournalTemplate::FREQ_MONTHLY:
                return $current->addMonthNoOverflow();
            case RecurringJournalTemplate::FREQ_QUARTERLY:
                return $current->addMonthsNoOverflow(3);
            case RecurringJournalTemplate::FREQ_YEARLY:
                return $current->addYearNoOverflow();
            default:
                return $current->addMonthNoOverflow();
        }
    }
}
