<?php

namespace App\Console\Commands;

use App\Models\RecurringBillTemplate;
use App\Services\Accounting\BillService;
use Illuminate\Console\Command;

class GenerateRecurringBills extends Command
{
    protected $signature = 'bills:generate-recurring';

    protected $description = 'Generate bills from active recurring bill templates';

    public function handle(BillService $billService): int
    {
        $templates = RecurringBillTemplate::active()->due()->get();

        $count = 0;

        foreach ($templates as $template) {
            $lines = $template->templateLines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'discount' => (float) $line->discount,
                    'tax_rate' => (float) $line->tax_rate,
                    'expense_account_id' => $line->expense_account_id,
                ];
            })->toArray();

            $data = [
                'company_id' => $template->company_id,
                'branch_id' => $template->branch_id,
                'vendor_id' => $template->vendor_id,
                'memo' => $template->memo,
                'lines' => $lines,
            ];

            try {
                $bill = $billService->create($data, $template->created_by);

                if ($template->auto_post) {
                    $billService->post($bill, $template->created_by);
                }

                $template->update([
                    'next_run_date' => $this->calculateNextRunDate($template),
                ]);

                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to generate bill for template '{$template->name}': {$e->getMessage()}");
            }
        }

        $this->info("Generated {$count} bills from recurring templates.");

        return Command::SUCCESS;
    }

    private function calculateNextRunDate(RecurringBillTemplate $template): \Carbon\Carbon
    {
        $current = $template->next_run_date->copy();

        switch ($template->frequency) {
            case RecurringBillTemplate::FREQ_WEEKLY:
                return $current->addWeek();
            case RecurringBillTemplate::FREQ_BIWEEKLY:
                return $current->addWeeks(2);
            case RecurringBillTemplate::FREQ_MONTHLY:
                return $current->addMonthNoOverflow();
            case RecurringBillTemplate::FREQ_QUARTERLY:
                return $current->addMonthsNoOverflow(3);
            case RecurringBillTemplate::FREQ_YEARLY:
                return $current->addYearNoOverflow();
            default:
                return $current->addMonthNoOverflow();
        }
    }
}
