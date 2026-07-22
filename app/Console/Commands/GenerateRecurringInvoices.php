<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoiceTemplate;
use App\Services\Accounting\InvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'Generate invoices from active recurring invoice templates';

    public function handle(InvoiceService $invoiceService): int
    {
        $templates = RecurringInvoiceTemplate::active()->due()->get();

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
                    'income_account_id' => $line->income_account_id,
                ];
            })->toArray();

            $data = [
                'company_id' => $template->company_id,
                'branch_id' => $template->branch_id,
                'customer_id' => $template->customer_id,
                'memo' => $template->memo,
                'lines' => $lines,
            ];

            try {
                $invoice = $invoiceService->create($data, $template->created_by);

                if ($template->auto_post) {
                    $invoiceService->post($invoice, $template->created_by);
                }

                $template->update([
                    'next_run_date' => $this->calculateNextRunDate($template),
                ]);

                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for template '{$template->name}': {$e->getMessage()}");
            }
        }

        $this->info("Generated {$count} invoices from recurring templates.");

        return Command::SUCCESS;
    }

    private function calculateNextRunDate(RecurringInvoiceTemplate $template): \Carbon\Carbon
    {
        $current = $template->next_run_date->copy();

        switch ($template->frequency) {
            case RecurringInvoiceTemplate::FREQ_WEEKLY:
                return $current->addWeek();
            case RecurringInvoiceTemplate::FREQ_BIWEEKLY:
                return $current->addWeeks(2);
            case RecurringInvoiceTemplate::FREQ_MONTHLY:
                return $current->addMonthNoOverflow();
            case RecurringInvoiceTemplate::FREQ_QUARTERLY:
                return $current->addMonthsNoOverflow(3);
            case RecurringInvoiceTemplate::FREQ_YEARLY:
                return $current->addYearNoOverflow();
            default:
                return $current->addMonthNoOverflow();
        }
    }
}
