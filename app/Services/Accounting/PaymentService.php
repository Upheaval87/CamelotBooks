<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentAllocation;
use App\Models\Invoice;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use App\Services\Accounting\ForeignCurrencyService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    protected JournalPostingEngine $postingEngine;
    protected ForeignCurrencyService $fxService;

    public function __construct(JournalPostingEngine $postingEngine, ForeignCurrencyService $fxService)
    {
        $this->postingEngine = $postingEngine;
        $this->fxService = $fxService;
    }

    // ─── Customer Receipts ───────────────────────────────────────────

    public function createCustomerPayment(array $data, int $userId): CustomerPayment
    {
        $companyId = $data['company_id'];

        $this->validateCustomer($companyId, $data['customer_id']);
        $this->validateBankAccount($companyId, $data['bank_account_id']);

        if (empty($data['allocations'])) {
            throw new InvalidArgumentException('At least one allocation is required.');
        }

        $paymentAmount = (float) $data['amount'];
        $totalAllocated = array_sum(array_map(fn($a) => (float) $a['amount'], $data['allocations']));

        if (round($totalAllocated, 2) !== round($paymentAmount, 2)) {
            throw new InvalidArgumentException(
                "Total allocations (" . number_format($totalAllocated, 2) .
                ") must equal payment amount (" . number_format($paymentAmount, 2) . ")."
            );
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $paymentNumber = $this->generateReceiptNumber($companyId);

            $payment = CustomerPayment::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'payment_number' => $paymentNumber,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'bank_account_id' => $data['bank_account_id'],
                'created_by' => $userId,
            ]);

            foreach ($data['allocations'] as $allocation) {
                CustomerPaymentAllocation::create([
                    'customer_payment_id' => $payment->id,
                    'invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            $this->logPaymentAction($payment, CustomerPayment::class, 'created', null, $payment->toArray(), $userId);

            return $payment;
        });
    }

    public function postCustomerPayment(CustomerPayment $payment, int $userId): CustomerPayment
    {
        if ($payment->journal_entry_id) {
            throw new InvalidArgumentException('This payment has already been posted.');
        }

        $companyId = $payment->company_id;
        $arAccount = $this->findAccountByCode($companyId, '1100');

        $bankAccount = Account::where('id', $payment->bank_account_id)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$bankAccount) {
            throw new InvalidArgumentException('Bank account not found or is not a bank account.');
        }

        return DB::transaction(function () use ($payment, $userId, $companyId, $arAccount, $bankAccount) {
            $oldValues = $payment->toArray();

            $customer = Customer::find($payment->customer_id);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $payment->payment_date->format('Y-m-d'),
                'source_module' => 'customer_payment',
                'reference' => $payment->payment_number,
                'memo' => "Payment from {$customer->name}",
                'branch_id' => $payment->branch_id,
                'lines' => [
                    [
                        'account_id' => $bankAccount->id,
                        'debit' => $payment->amount,
                        'credit' => 0,
                        'memo' => "Payment from {$customer->name}",
                        'entity_type' => CustomerPayment::class,
                        'entity_id' => $payment->id,
                    ],
                    [
                        'account_id' => $arAccount->id,
                        'debit' => 0,
                        'credit' => $payment->amount,
                        'memo' => "Payment from {$customer->name}",
                        'entity_type' => CustomerPayment::class,
                        'entity_id' => $payment->id,
                    ],
                ],
            ]);

            BankTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $payment->branch_id,
                'bank_account_id' => $payment->bank_account_id,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'deposit',
                'source_type' => 'customer_payment',
                'source_id' => $payment->id,
                'date' => $payment->payment_date,
                'description' => "Payment from {$customer->name}",
                'amount' => $payment->amount,
                'created_by' => $userId,
            ]);

            $payment->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            $allocations = $payment->allocations()->get();

            foreach ($allocations as $allocation) {
                $invoice = Invoice::find($allocation->invoice_id);

                $newAmountPaid = round((float) $invoice->amount_paid + (float) $allocation->amount, 2);

                $newStatus = $newAmountPaid >= (float) $invoice->amount
                    ? Invoice::STATUS_PAID
                    : Invoice::STATUS_PARTIALLY_PAID;

                $invoice->update([
                    'amount_paid' => $newAmountPaid,
                    'status' => $newStatus,
                ]);
            }

            $this->logPaymentAction($payment, CustomerPayment::class, 'posted', $oldValues, $payment->toArray(), $userId);

            $this->settleForeignCurrencyGainLoss($payment->company_id, $payment->allocations()->get(), $userId);

            return $payment;
        });
    }

    // ─── Vendor Payments ─────────────────────────────────────────────

    public function createVendorPayment(array $data, int $userId, string $status = VendorPayment::STATUS_DRAFT): VendorPayment
    {
        $companyId = $data['company_id'];

        $this->validateVendor($companyId, $data['vendor_id']);
        $this->validateBankAccount($companyId, $data['bank_account_id']);

        if (empty($data['allocations'])) {
            throw new InvalidArgumentException('At least one allocation is required.');
        }

        $paymentAmount = (float) $data['amount'];
        $totalAllocated = array_sum(array_map(fn($a) => (float) $a['amount'], $data['allocations']));

        if (round($totalAllocated, 2) !== round($paymentAmount, 2)) {
            throw new InvalidArgumentException(
                "Total allocations (" . number_format($totalAllocated, 2) .
                ") must equal payment amount (" . number_format($paymentAmount, 2) . ")."
            );
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $status) {
            $paymentNumber = $this->generatePaymentNumber($companyId);

            $payment = VendorPayment::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'payment_number' => $paymentNumber,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'bank_account_id' => $data['bank_account_id'],
                'status' => $status,
                'created_by' => $userId,
            ]);

            foreach ($data['allocations'] as $allocation) {
                VendorPaymentAllocation::create([
                    'vendor_payment_id' => $payment->id,
                    'bill_id' => $allocation['bill_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            $this->logPaymentAction($payment, VendorPayment::class, 'created', null, $payment->toArray(), $userId);

            return $payment;
        });
    }

    public function submitVendorPayment(VendorPayment $payment, int $userId): VendorPayment
    {
        if ($payment->status !== VendorPayment::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft vendor payments can be submitted for approval.');
        }

        $oldValues = $payment->toArray();

        $payment->update([
            'status' => VendorPayment::STATUS_PENDING_APPROVAL,
        ]);

        $this->logPaymentAction($payment, VendorPayment::class, 'submitted_for_approval', $oldValues, $payment->toArray(), $userId);

        return $payment->fresh();
    }

    public function rejectVendorPayment(VendorPayment $payment, int $userId, ?string $reason = null): VendorPayment
    {
        if ($payment->status !== VendorPayment::STATUS_PENDING_APPROVAL) {
            throw new InvalidArgumentException('Only pending vendor payments can be rejected.');
        }

        $oldValues = $payment->toArray();

        $payment->update([
            'status' => VendorPayment::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        $this->logPaymentAction($payment, VendorPayment::class, 'rejected', $oldValues, $payment->toArray(), $userId);

        return $payment->fresh();
    }

    public function postVendorPayment(VendorPayment $payment, int $userId): VendorPayment
    {
        if ($payment->journal_entry_id) {
            throw new InvalidArgumentException('This payment has already been posted.');
        }

        if (!in_array($payment->status, [VendorPayment::STATUS_DRAFT, VendorPayment::STATUS_PENDING_APPROVAL], true)) {
            throw new InvalidArgumentException('Only draft or pending vendor payments can be posted.');
        }

        $companyId = $payment->company_id;
        $apAccount = $this->findAccountByCode($companyId, '2000');

        $bankAccount = Account::where('id', $payment->bank_account_id)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$bankAccount) {
            throw new InvalidArgumentException('Bank account not found or is not a bank account.');
        }

        return DB::transaction(function () use ($payment, $userId, $companyId, $apAccount, $bankAccount) {
            $oldValues = $payment->toArray();

            $vendor = Vendor::find($payment->vendor_id);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $payment->payment_date->format('Y-m-d'),
                'source_module' => 'vendor_payment',
                'reference' => $payment->payment_number,
                'memo' => "Payment to {$vendor->name}",
                'branch_id' => $payment->branch_id,
                'lines' => [
                    [
                        'account_id' => $apAccount->id,
                        'debit' => $payment->amount,
                        'credit' => 0,
                        'memo' => "Payment to {$vendor->name}",
                        'entity_type' => VendorPayment::class,
                        'entity_id' => $payment->id,
                    ],
                    [
                        'account_id' => $bankAccount->id,
                        'debit' => 0,
                        'credit' => $payment->amount,
                        'memo' => "Payment to {$vendor->name}",
                        'entity_type' => VendorPayment::class,
                        'entity_id' => $payment->id,
                    ],
                ],
            ]);

            BankTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $payment->branch_id,
                'bank_account_id' => $payment->bank_account_id,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'withdrawal',
                'source_type' => 'vendor_payment',
                'source_id' => $payment->id,
                'date' => $payment->payment_date,
                'description' => "Payment to {$vendor->name}",
                'amount' => -$payment->amount,
                'created_by' => $userId,
            ]);

            $payment->update([
                'journal_entry_id' => $journalEntry->id,
                'status' => VendorPayment::STATUS_POSTED,
            ]);

            $allocations = $payment->allocations()->get();

            foreach ($allocations as $allocation) {
                $bill = \App\Models\Bill::find($allocation->bill_id);

                $newAmountPaid = round((float) $bill->amount_paid + (float) $allocation->amount, 2);

                $newStatus = $newAmountPaid >= (float) $bill->amount
                    ? \App\Models\Bill::STATUS_PAID
                    : \App\Models\Bill::STATUS_PARTIALLY_PAID;

                $bill->update([
                    'amount_paid' => $newAmountPaid,
                    'status' => $newStatus,
                ]);
            }

            $this->logPaymentAction($payment, VendorPayment::class, 'posted', $oldValues, $payment->toArray(), $userId);

            $this->settleVendorForeignCurrencyGainLoss($payment->company_id, $payment->allocations()->get(), $userId);

            return $payment;
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    protected function generateReceiptNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'REC-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastPayment = CustomerPayment::where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderByDesc('payment_number')
            ->first();

        if ($lastPayment) {
            $lastSequence = (int) substr($lastPayment->payment_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function generatePaymentNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'PAY-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastPayment = VendorPayment::where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderByDesc('payment_number')
            ->first();

        if ($lastPayment) {
            $lastSequence = (int) substr($lastPayment->payment_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function validateCustomer(int $companyId, int $customerId): void
    {
        $customer = Customer::where('id', $customerId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            throw new InvalidArgumentException("Customer ID {$customerId} not found or inactive for this company.");
        }
    }

    protected function validateVendor(int $companyId, int $vendorId): void
    {
        $vendor = Vendor::where('id', $vendorId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$vendor) {
            throw new InvalidArgumentException("Vendor ID {$vendorId} not found or inactive for this company.");
        }
    }

    protected function validateBankAccount(int $companyId, int $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Bank account ID {$accountId} not found or is not a bank account for this company.");
        }
    }

    protected function findAccountByCode(int $companyId, string $code): Account
    {
        $account = Account::where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account with code {$code} not found for this company.");
        }

        return $account;
    }

    protected function logPaymentAction($payment, string $modelClass, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $payment->company_id,
            'journalable_type' => $modelClass,
            'journalable_id' => $payment->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    protected function settleForeignCurrencyGainLoss(int $companyId, $allocations, int $userId): void
    {
        $company = \App\Models\Company::find($companyId);
        $baseCurrency = $company->base_currency ?? 'USD';

        $totalGainLoss = 0;
        $payment = null;

        foreach ($allocations as $allocation) {
            $invoice = Invoice::find($allocation->invoice_id);
            if (!$invoice || strtoupper($invoice->currency ?? 'USD') === strtoupper($baseCurrency)) {
                continue;
            }

            if (!$payment) {
                $payment = $allocation->payment;
            }

            $originalBase = (float) $invoice->base_amount;
            $foreignAmount = (float) $invoice->amount;
            if ($foreignAmount <= 0) {
                continue;
            }

            $proportionateBase = round($originalBase * ((float) $allocation->amount / $foreignAmount), 2);
            $gainLoss = round((float) $allocation->amount - $proportionateBase, 2);
            $totalGainLoss += $gainLoss;
        }

        if ($payment && abs($totalGainLoss) >= 0.01) {
            $this->fxService->postRealizedGainLossOnCustomerPayment($payment, $totalGainLoss, $userId);
        }
    }

    protected function settleVendorForeignCurrencyGainLoss(int $companyId, $allocations, int $userId): void
    {
        $company = \App\Models\Company::find($companyId);
        $baseCurrency = $company->base_currency ?? 'USD';

        $totalGainLoss = 0;
        $payment = null;

        foreach ($allocations as $allocation) {
            $bill = \App\Models\Bill::find($allocation->bill_id);
            if (!$bill || strtoupper($bill->currency ?? 'USD') === strtoupper($baseCurrency)) {
                continue;
            }

            if (!$payment) {
                $payment = $allocation->payment;
            }

            $originalBase = (float) $bill->base_amount;
            $foreignAmount = (float) $bill->amount;
            if ($foreignAmount <= 0) {
                continue;
            }

            $proportionateBase = round($originalBase * ((float) $allocation->amount / $foreignAmount), 2);
            $gainLoss = round((float) $allocation->amount - $proportionateBase, 2);
            $totalGainLoss += $gainLoss;
        }

        if ($payment && abs($totalGainLoss) >= 0.01) {
            $this->fxService->postRealizedGainLossOnVendorPayment($payment, $totalGainLoss, $userId);
        }
    }
}
