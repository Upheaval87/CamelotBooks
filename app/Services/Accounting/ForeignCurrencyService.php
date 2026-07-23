<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CustomerPayment;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\DB;

class ForeignCurrencyService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
    ) {
    }

    public function convert(int $companyId, float $amount, string $fromCurrency, string $toCurrency, string $date): float
    {
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return $amount;
        }

        $rate = ExchangeRate::getRate($companyId, $fromCurrency, $toCurrency, $date);

        if ($rate === null) {
            throw new \InvalidArgumentException(
                "No exchange rate found for {$fromCurrency} to {$toCurrency} on or before {$date}."
            );
        }

        return round($amount * $rate, 2);
    }

    public function postInvoiceInForeignCurrency(
        Invoice $invoice,
        array &$jeLines,
        int $userId
    ): void {
        $companyId = $invoice->company_id;
        $company = Company::find($companyId);
        $invoiceCurrency = $invoice->currency ?? 'USD';
        $baseCurrency = $company->base_currency;

        if (strtoupper($invoiceCurrency) === strtoupper($baseCurrency)) {
            $invoice->update([
                'exchange_rate' => 1,
                'base_amount' => $invoice->amount,
            ]);

            return;
        }

        $rate = ExchangeRate::getRate($companyId, $invoiceCurrency, $baseCurrency, $invoice->invoice_date->format('Y-m-d'));

        if ($rate === null) {
            throw new \InvalidArgumentException(
                "No exchange rate found for {$invoiceCurrency} to {$baseCurrency} on {$invoice->invoice_date->format('Y-m-d')}."
            );
        }

        $baseAmount = round((float) $invoice->amount * $rate, 2);

        foreach ($jeLines as &$line) {
            $line['foreign_amount'] = $line['debit'] > 0 ? $line['debit'] : ($line['credit'] > 0 ? $line['credit'] : 0);
            $line['foreign_currency'] = $invoiceCurrency;
            $line['exchange_rate'] = $rate;

            if ($line['debit'] > 0) {
                $line['debit'] = round($line['debit'] * $rate, 2);
            }
            if ($line['credit'] > 0) {
                $line['credit'] = round($line['credit'] * $rate, 2);
            }
        }
        unset($line);

        $invoice->update([
            'exchange_rate' => $rate,
            'base_amount' => $baseAmount,
        ]);
    }

    public function postBillInForeignCurrency(
        \App\Models\Bill $bill,
        array &$jeLines,
        int $userId
    ): void {
        $companyId = $bill->company_id;
        $company = Company::find($companyId);
        $billCurrency = $bill->currency ?? 'USD';
        $baseCurrency = $company->base_currency;

        if (strtoupper($billCurrency) === strtoupper($baseCurrency)) {
            $bill->update([
                'exchange_rate' => 1,
                'base_amount' => $bill->amount,
            ]);

            return;
        }

        $rate = ExchangeRate::getRate($companyId, $billCurrency, $baseCurrency, $bill->bill_date->format('Y-m-d'));

        if ($rate === null) {
            throw new \InvalidArgumentException(
                "No exchange rate found for {$billCurrency} to {$baseCurrency} on {$bill->bill_date->format('Y-m-d')}."
            );
        }

        $baseAmount = round((float) $bill->amount * $rate, 2);

        foreach ($jeLines as &$line) {
            $line['foreign_amount'] = $line['debit'] > 0 ? $line['debit'] : ($line['credit'] > 0 ? $line['credit'] : 0);
            $line['foreign_currency'] = $billCurrency;
            $line['exchange_rate'] = $rate;

            if ($line['debit'] > 0) {
                $line['debit'] = round($line['debit'] * $rate, 2);
            }
            if ($line['credit'] > 0) {
                $line['credit'] = round($line['credit'] * $rate, 2);
            }
        }
        unset($line);

        $bill->update([
            'exchange_rate' => $rate,
            'base_amount' => $baseAmount,
        ]);
    }

    public function calculateRealizedGainLoss(
        int $companyId,
        float $originalBaseAmount,
        float $currentBaseAmount,
    ): float {
        return round($currentBaseAmount - $originalBaseAmount, 2);
    }

    public function postRealizedGainLossOnCustomerPayment(
        CustomerPayment $payment,
        float $gainLoss,
        int $userId
    ): void {
        if (abs($gainLoss) < 0.01) {
            return;
        }

        $companyId = $payment->company_id;
        $fxGainLossAccount = $this->findFxAccount($companyId, '7200');

        $lines = [];
        if ($gainLoss > 0) {
            $lines[] = [
                'account_id' => $fxGainLossAccount->id,
                'debit' => 0,
                'credit' => abs($gainLoss),
                'memo' => 'Realized FX gain on payment ' . $payment->payment_number,
            ];
        } else {
            $lines[] = [
                'account_id' => $fxGainLossAccount->id,
                'debit' => abs($gainLoss),
                'credit' => 0,
                'memo' => 'Realized FX loss on payment ' . $payment->payment_number,
            ];
        }

        $this->postingEngine->post([
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $payment->payment_date->format('Y-m-d'),
            'source_module' => 'realized_fx_gain_loss',
            'reference' => $payment->payment_number,
            'memo' => 'Realized FX gain/loss on ' . $payment->payment_number,
            'skip_period_validation' => true,
            'lines' => $lines,
        ]);
    }

    public function postRealizedGainLossOnVendorPayment(
        VendorPayment $payment,
        float $gainLoss,
        int $userId
    ): void {
        if (abs($gainLoss) < 0.01) {
            return;
        }

        $companyId = $payment->company_id;
        $fxGainLossAccount = $this->findFxAccount($companyId, '7200');

        $lines = [];
        if ($gainLoss > 0) {
            $lines[] = [
                'account_id' => $fxGainLossAccount->id,
                'debit' => 0,
                'credit' => abs($gainLoss),
                'memo' => 'Realized FX gain on payment ' . $payment->payment_number,
            ];
        } else {
            $lines[] = [
                'account_id' => $fxGainLossAccount->id,
                'debit' => abs($gainLoss),
                'credit' => 0,
                'memo' => 'Realized FX loss on payment ' . $payment->payment_number,
            ];
        }

        $this->postingEngine->post([
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $payment->payment_date->format('Y-m-d'),
            'source_module' => 'realized_fx_gain_loss',
            'reference' => $payment->payment_number,
            'memo' => 'Realized FX gain/loss on ' . $payment->payment_number,
            'skip_period_validation' => true,
            'lines' => $lines,
        ]);
    }

    public function revalueForeignBalances(int $companyId, int $userId, string $asOfDate): float
    {
        $company = Company::find($companyId);
        $baseCurrency = $company->base_currency;

        $totalGainLoss = 0;

        $openInvoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->where('currency', '!=', $baseCurrency)
            ->get();

        foreach ($openInvoices as $invoice) {
            $originalBase = (float) ($invoice->base_amount ?? $invoice->amount);
            $currentBase = $this->convert(
                $companyId,
                (float) $invoice->amount - (float) $invoice->amount_paid,
                $invoice->currency,
                $baseCurrency,
                $asOfDate
            );

            $gainLoss = $this->calculateRealizedGainLoss($companyId, $originalBase * ((float) $invoice->amount - (float) $invoice->amount_paid) / max((float) $invoice->amount, 0.01), $currentBase);

            if (abs($gainLoss) >= 0.01) {
                $totalGainLoss += $gainLoss;
            }
        }

        $openBills = \App\Models\Bill::where('company_id', $companyId)
            ->whereIn('status', [\App\Models\Bill::STATUS_APPROVED, \App\Models\Bill::STATUS_PARTIALLY_PAID])
            ->where('currency', '!=', $baseCurrency)
            ->get();

        foreach ($openBills as $bill) {
            $originalBase = (float) ($bill->base_amount ?? $bill->amount);
            $currentBase = $this->convert(
                $companyId,
                (float) $bill->amount - (float) $bill->amount_paid,
                $bill->currency,
                $baseCurrency,
                $asOfDate
            );

            $gainLoss = $this->calculateRealizedGainLoss($companyId, $originalBase * ((float) $bill->amount - (float) $bill->amount_paid) / max((float) $bill->amount, 0.01), $currentBase);

            if (abs($gainLoss) >= 0.01) {
                $totalGainLoss += $gainLoss;
            }
        }

        if (abs($totalGainLoss) < 0.01) {
            return 0;
        }

        $unrealizedAccount = $this->findFxAccount($companyId, '7300');
        $arAccount = Account::where('company_id', $companyId)->where('code', '1100')->first();
        $apAccount = Account::where('company_id', $companyId)->where('code', '2000')->first();

        if ($totalGainLoss > 0) {
            $jeLines = [
                [
                    'account_id' => $arAccount?->id ?? $unrealizedAccount->id,
                    'debit' => abs($totalGainLoss),
                    'credit' => 0,
                    'memo' => 'Unrealized FX gain - receivable increase',
                ],
                [
                    'account_id' => $unrealizedAccount->id,
                    'debit' => 0,
                    'credit' => abs($totalGainLoss),
                    'memo' => 'Unrealized FX gain on revaluation',
                ],
            ];
        } else {
            $jeLines = [
                [
                    'account_id' => $unrealizedAccount->id,
                    'debit' => abs($totalGainLoss),
                    'credit' => 0,
                    'memo' => 'Unrealized FX loss on revaluation',
                ],
                [
                    'account_id' => $arAccount?->id ?? $unrealizedAccount->id,
                    'debit' => 0,
                    'credit' => abs($totalGainLoss),
                    'memo' => 'Unrealized FX loss - receivable decrease',
                ],
            ];
        }

        $this->postingEngine->post([
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $asOfDate,
            'source_module' => 'unrealized_fx_revaluation',
            'reference' => 'REVAL-' . $asOfDate,
            'memo' => 'Period-end FX revaluation as of ' . $asOfDate,
            'skip_period_validation' => true,
            'lines' => $jeLines,
        ]);

        return $totalGainLoss;
    }

    private function findFxAccount(int $companyId, string $code): Account
    {
        $account = Account::where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new \InvalidArgumentException("FX account {$code} not found for company {$companyId}.");
        }

        return $account;
    }
}
