<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use InvalidArgumentException;

class SalesPostingService
{
    public function __construct(
        protected JournalPostingEngine $postingEngine,
    ) {}

    /**
     * Post a sale (POS or Sales Receipt) journal entry.
     *
     * @param array $context [
     *   'company_id' => int,
     *   'user_id' => int,
     *   'source_module' => string, // 'pos' or 'sales_receipt'
     *   'document_number' => string,
     *   'date' => string (Y-m-d),
     *   'memo' => string,
     *   'lines' => [ // each line from the sale
     *     'product_name' => string,
     *     'income_account_id' => int,
     *     'line_total' => float,
     *     'tax_amount' => float,
     *     'cost_of_goods' => float,
     *     'tracked_as_inventory' => bool,
     *   ],
     *   'payments' => [
     *     [
     *       'amount' => float,
     *       'payment_method_name' => string,
     *       'clearing_account_id' => int|null, // null for bank transfer
     *       'bank_account_id' => int|null, // set for bank transfer
     *     ],
     *   ],
     * ]
     */
    public function postSale(array $context): JournalEntry
    {
        $companyId = $context['company_id'];
        $userId = $context['user_id'];
        $sourceModule = $context['source_module'];
        $documentNumber = $context['document_number'];
        $date = $context['date'] ?? now()->toDateString();
        $memo = $context['memo'] ?? "{$sourceModule} {$documentNumber}";
        $lines = $context['lines'];
        $payments = $context['payments'];

        $taxPayable = Account::where('company_id', $companyId)->where('code', '2300')->first();
        $cogsAccount = Account::where('company_id', $companyId)->where('code', '5000')->first();
        $invAssetAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();
        $defaultRevenue = Account::where('company_id', $companyId)->where('code', '4000')->first();

        $jeLines = [];
        $totalDebits = 0;
        $totalCredits = 0;

        // 1. DR: Payment accounts (consolidated per account)
        foreach ($payments as $payment) {
            // Bank Transfer: DR the bank account directly
            $accountId = $payment['bank_account_id'] ?? $payment['clearing_account_id'];
            if (!$accountId) {
                throw new InvalidArgumentException("Payment method '{$payment['payment_method_name']}' has no target account.");
            }

            $existingKey = null;
            foreach ($jeLines as $idx => $jl) {
                if ($jl['account_id'] === $accountId && $jl['debit'] > 0) {
                    $existingKey = $idx;
                    break;
                }
            }
            if ($existingKey !== null) {
                $jeLines[$existingKey]['debit'] = round($jeLines[$existingKey]['debit'] + $payment['amount'], 2);
            } else {
                $label = $payment['bank_account_id'] ? 'Bank Transfer' : $payment['payment_method_name'];
                $jeLines[] = [
                    'account_id' => $accountId,
                    'debit' => $payment['amount'],
                    'credit' => 0,
                    'description' => "{$memo} – {$label}",
                ];
            }
        }

        // 2. CR: Revenue per line + CR: Tax + DR/CR: COGS/Inventory
        foreach ($lines as $line) {
            $revenueAccountId = $line['income_account_id'] ?? $defaultRevenue?->id;
            if (!$revenueAccountId) {
                throw new InvalidArgumentException("No revenue account for product: {$line['product_name']}");
            }

            $lineNet = round($line['line_total'] - $line['tax_amount'], 2);

            // CR: Revenue
            $existingKey = null;
            foreach ($jeLines as $idx => $jl) {
                if ($jl['account_id'] === $revenueAccountId && $jl['credit'] > 0 && empty($jl['_tax'])) {
                    $existingKey = $idx;
                    break;
                }
            }
            if ($existingKey !== null) {
                $jeLines[$existingKey]['credit'] = round($jeLines[$existingKey]['credit'] + $lineNet, 2);
            } else {
                $jeLines[] = [
                    'account_id' => $revenueAccountId,
                    'debit' => 0,
                    'credit' => $lineNet,
                    'description' => "{$memo} – {$line['product_name']}",
                ];
            }

            // CR: Tax Payable
            if (($line['tax_amount'] ?? 0) > 0 && $taxPayable) {
                $jeLines[] = [
                    'account_id' => $taxPayable->id,
                    'debit' => 0,
                    'credit' => $line['tax_amount'],
                    'description' => "{$memo} – Tax",
                    '_tax' => true,
                ];
            }

            // DR COGS / CR Inventory (tracked items only)
            if (!empty($line['tracked_as_inventory']) && ($line['cost_of_goods'] ?? 0) > 0) {
                if ($cogsAccount) {
                    $jeLines[] = [
                        'account_id' => $cogsAccount->id,
                        'debit' => $line['cost_of_goods'],
                        'credit' => 0,
                        'description' => "{$memo} – COGS",
                    ];
                }
                if ($invAssetAccount) {
                    $jeLines[] = [
                        'account_id' => $invAssetAccount->id,
                        'debit' => 0,
                        'credit' => $line['cost_of_goods'],
                        'description' => "{$memo} – Inventory",
                    ];
                }
            }
        }

        // 3. Rounding adjustment (like InvoiceService)
        foreach ($jeLines as &$jl) {
            unset($jl['_tax']);
        }
        unset($jl);

        $totalDebits = array_sum(array_map(fn($jl) => $jl['debit'], $jeLines));
        $totalCredits = array_sum(array_map(fn($jl) => $jl['credit'], $jeLines));
        $diff = round($totalDebits - $totalCredits, 2);

        if (abs($diff) > 0.001) {
            if (abs($diff) <= 0.05) {
                $roundingAccount = Account::where('company_id', $companyId)->where('code', '9999')->first();
                if ($roundingAccount) {
                    if ($diff > 0) {
                        $jeLines[] = [
                            'account_id' => $roundingAccount->id,
                            'debit' => 0,
                            'credit' => abs($diff),
                            'description' => "{$memo} – Rounding",
                        ];
                    } else {
                        $jeLines[] = [
                            'account_id' => $roundingAccount->id,
                            'debit' => abs($diff),
                            'credit' => 0,
                            'description' => "{$memo} – Rounding",
                        ];
                    }
                }
            } else {
                throw new InvalidArgumentException("Journal entry is unbalanced by {$diff}. Maximum rounding adjustment is 0.05.");
            }
        }

        return $this->postingEngine->post([
            'company_id' => $companyId,
            'date' => $date,
            'reference' => strtoupper(substr($sourceModule, 0, 3)) . "-{$documentNumber}",
            'memo' => $memo,
            'lines' => $jeLines,
            'created_by' => $userId,
            'source_module' => $sourceModule,
        ]);
    }
}
