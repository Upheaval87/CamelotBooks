<?php

namespace App\Services\POS;

use App\Models\DefaultAccountMapping;
use App\Models\AuditLog;
use App\Models\EisTerminal;
use App\Models\JournalEntry;
use App\Models\NumberingSequence;
use App\Models\PosCashierSession;
use App\Models\PosPayment;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Product;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Admin\NumberingSequenceService;
use App\Services\EIS\EisSubmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PosSaleService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private InventoryService $inventoryService,
        private NumberingSequenceService $numberingService,
        private EisSubmissionService $eisService
    ) {}

    public function checkout(array $data, int $userId): PosSale
    {
        $this->validateCheckoutData($data);

        $sale = DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];
            $sessionId = $data['cashier_session_id'] ?? null;

            if ($sessionId) {
                $session = PosCashierSession::findOrFail($sessionId);
                if ($session->isClosed()) {
                    throw new InvalidArgumentException('Cannot create sale on a closed till session.');
                }
            }

            $saleNumber = $this->numberingService->getNextNumber($companyId, 'pos_sale');

            $sale = PosSale::create([
                'company_id' => $companyId,
                'terminal_id' => $data['terminal_id'],
                'cashier_session_id' => $sessionId,
                'customer_id' => $data['customer_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'sale_number' => $saleNumber,
                'reference' => $data['reference'] ?? null,
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'status' => PosSale::STATUS_DRAFT,
                'is_on_account' => false,
            ]);

            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;
            $totalCOGS = 0;

            foreach ($data['lines'] as $lineData) {
                $product = Product::where('id', $lineData['product_id'])
                    ->where('company_id', $companyId)
                    ->firstOrFail();

                $quantity = (float) $lineData['quantity'];
                $transactionQty = $quantity;
                if (!empty($lineData['conversion_factor']) && !empty($lineData['transaction_qty'])) {
                    $transactionQty = (float) $lineData['transaction_qty'];
                    $quantity = round($transactionQty * (float) $lineData['conversion_factor'], 4);
                }
                $unitPrice = (float) $lineData['unit_price'];
                $discountAmount = (float) ($lineData['discount_amount'] ?? 0);
                $taxRate = (float) ($lineData['tax_rate'] ?? ($product->is_taxable ? $product->tax_rate : 0));

                $lineSubtotal = round($transactionQty * $unitPrice, 2);
                $lineAfterDiscount = round($lineSubtotal - $discountAmount, 2);
                $lineTaxAmount = round($lineAfterDiscount * ($taxRate / 100), 2);
                $lineTotal = round($lineAfterDiscount + $lineTaxAmount, 2);

                $costOfGoods = null;
                if ($product->tracked_as_inventory && $quantity > 0) {
                    $consumedLayers = $this->inventoryService->consumeStock(
                        $companyId,
                        $product->id,
                        $data['branch_id'] ?? null,
                        $quantity,
                        now()->toDateString()
                    );
                    $totalCOGS += collect($consumedLayers)->sum(fn ($l) => $l['total_cost']);
                    $costOfGoods = collect($consumedLayers)->sum(fn ($l) => $l['total_cost']);
                }

                PosSaleLine::create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'transaction_uom' => $lineData['transaction_uom'] ?? null,
                    'transaction_qty' => $lineData['transaction_qty'] ?? null,
                    'conversion_factor' => $lineData['conversion_factor'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'discount_type' => $lineData['discount_type'] ?? null,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTaxAmount,
                    'line_total' => $lineTotal,
                    'cost_of_goods' => $costOfGoods,
                ]);

                $subtotal += $lineSubtotal;
                $discountTotal += $discountAmount;
                $taxTotal += $lineTaxAmount;
            }

            $total = round($subtotal - $discountTotal + $taxTotal, 2);

            $totalPayments = 0;
            foreach ($data['payments'] as $paymentData) {
                $paymentMethod = PosPaymentMethod::where('id', $paymentData['payment_method_id'])
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->firstOrFail();

                $paymentAmount = (float) $paymentData['amount'];
                $totalPayments += $paymentAmount;

                PosPayment::create([
                    'pos_sale_id' => $sale->id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $paymentAmount,
                    'cash_tendered' => $paymentData['cash_tendered'] ?? null,
                    'change_given' => $paymentData['change_given'] ?? null,
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'processor_name' => $paymentData['processor_name'] ?? null,
                    'account_name' => $paymentData['account_name'] ?? null,
                    'institution' => $paymentData['institution'] ?? null,
                ]);
            }

            if (round($totalPayments, 2) < round($total, 2)) {
                $bottleCredit = (float) ($data['bottle_credit_applied'] ?? 0);
                if ($bottleCredit > 0 && round($totalPayments + $bottleCredit, 2) < round($total, 2)) {
                    throw new InvalidArgumentException(
                        'Total payments ($' . number_format($totalPayments + $bottleCredit, 2) . ' including bottle credit) is less than sale total ($' . number_format($total, 2) . ').'
                    );
                }
            }

            $sale->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            $journalEntry = $this->postSaleEntry($sale, $companyId, $userId);

            $sale->update([
                'status' => PosSale::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
            ]);

            AuditLog::log(
                $companyId,
                $userId,
                PosSale::class,
                $sale->id,
                'pos.sale.created',
                null,
                [
                    'sale_number' => $sale->sale_number,
                    'total' => $sale->total,
                    'payment_count' => count($data['payments']),
                    'line_count' => count($data['lines']),
                ],
                "POS Sale {$sale->sale_number} – $" . number_format($sale->total, 2)
            );

            return $sale->fresh(['lines.product', 'payments.paymentMethod', 'journalEntry']);
        });

        $this->submitToEis($sale);

        return $sale;
    }

    private function submitToEis(PosSale $sale): void
    {
        try {
            $terminal = EisTerminal::where('company_id', $sale->company_id)
                ->where('status', EisTerminal::STATUS_ACTIVE)
                ->where('should_block_terminal', false)
                ->first();

            if (!$terminal) {
                return;
            }

            $submission = $this->eisService->submitInvoice($terminal, $sale);

            $sale->update(['eis_submission_id' => $submission->id]);
        } catch (\Exception $e) {
            Log::warning('EIS submission failed for POS sale', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function postSaleEntry(PosSale $sale, int $companyId, int $userId): JournalEntry
    {
        $lines = $sale->lines()->with('product')->get();
        $payments = $sale->payments()->with('paymentMethod')->get();

        $taxPayable = DefaultAccountMapping::getAccount($companyId, 'tax_payable');
        $cogsAccount = DefaultAccountMapping::getAccount($companyId, 'default_expense');
        $invAssetAccount = DefaultAccountMapping::getAccount($companyId, 'inventory_asset');
        $defaultRevenue = DefaultAccountMapping::getAccount($companyId, 'default_revenue');

        $jeLines = [];

        // Debit: clearing accounts from each payment
        foreach ($payments as $payment) {
            $clearingAccountId = $payment->paymentMethod->clearing_account_id;
            $existingKey = null;
            foreach ($jeLines as $idx => $jl) {
                if ($jl['account_id'] === $clearingAccountId && $jl['debit'] > 0) {
                    $existingKey = $idx;
                    break;
                }
            }
            if ($existingKey !== null) {
                $jeLines[$existingKey]['debit'] = round($jeLines[$existingKey]['debit'] + $payment->amount, 2);
            } else {
                $jeLines[] = [
                    'account_id' => $clearingAccountId,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'description' => "POS Sale {$sale->sale_number} – {$payment->paymentMethod->name}",
                ];
            }
        }

        // Credit: revenue per line
        foreach ($lines as $line) {
            $revenueAccountId = $line->product->income_account_id ?? $defaultRevenue?->id;
            if (!$revenueAccountId) {
                throw new InvalidArgumentException("No revenue account found for product: {$line->product->name}");
            }

            $lineNet = round($line->line_total - $line->tax_amount, 2);

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
                    'description' => "POS Sale {$sale->sale_number} – {$line->product->name}",
                ];
            }

            // Credit: tax payable
            if ($line->tax_amount > 0 && $taxPayable) {
                $jeLines[] = [
                    'account_id' => $taxPayable->id,
                    'debit' => 0,
                    'credit' => $line->tax_amount,
                    'description' => "POS Sale {$sale->sale_number} – Tax",
                    '_tax' => true,
                ];
            }

            // DR COGS / CR Inventory for tracked items
            if ($line->product->tracked_as_inventory && $line->cost_of_goods > 0) {
                if ($cogsAccount) {
                    $jeLines[] = [
                        'account_id' => $cogsAccount->id,
                        'debit' => $line->cost_of_goods,
                        'credit' => 0,
                        'description' => "POS Sale {$sale->sale_number} – COGS",
                    ];
                }
                if ($invAssetAccount) {
                    $jeLines[] = [
                        'account_id' => $invAssetAccount->id,
                        'debit' => 0,
                        'credit' => $line->cost_of_goods,
                        'description' => "POS Sale {$sale->sale_number} – Inventory",
                    ];
                }
            }
        }

        return $this->postingEngine->post([
            'company_id' => $companyId,
            'date' => now()->toDateString(),
            'reference' => "POS-{$sale->sale_number}",
            'memo' => "POS Sale {$sale->sale_number}",
            'lines' => $jeLines,
            'created_by' => $userId,
            'source_module' => 'pos',
        ]);
    }

    private function validateCheckoutData(array $data): void
    {
        if (empty($data['company_id'])) {
            throw new InvalidArgumentException('company_id is required.');
        }
        if (empty($data['terminal_id'])) {
            throw new InvalidArgumentException('terminal_id is required.');
        }
        if (empty($data['lines']) || !is_array($data['lines'])) {
            throw new InvalidArgumentException('At least one sale line is required.');
        }
        if (empty($data['payments']) || !is_array($data['payments'])) {
            throw new InvalidArgumentException('At least one payment is required.');
        }

        foreach ($data['lines'] as $i => $line) {
            if (empty($line['product_id'])) {
                throw new InvalidArgumentException("Line {$i}: product_id is required.");
            }
            if (empty($line['quantity']) || (float) $line['quantity'] <= 0) {
                throw new InvalidArgumentException("Line {$i}: quantity must be positive.");
            }
            if (empty($line['unit_price']) || (float) $line['unit_price'] < 0) {
                throw new InvalidArgumentException("Line {$i}: unit_price is required.");
            }
        }

        foreach ($data['payments'] as $i => $payment) {
            if (empty($payment['payment_method_id'])) {
                throw new InvalidArgumentException("Payment {$i}: payment_method_id is required.");
            }
            if (empty($payment['amount']) || (float) $payment['amount'] <= 0) {
                throw new InvalidArgumentException("Payment {$i}: amount must be positive.");
            }
        }
    }

    /**
     * Void a posted sale: reverse the JE, restock inventory, update status.
     */
    public function voidSale(PosSale $sale, int $companyId, int $userId): PosSale
    {
        return DB::transaction(function () use ($sale, $companyId, $userId) {
            // 1. Reverse stock
            foreach ($sale->lines()->with('product')->get() as $line) {
                if ($line->product && $line->product->tracked_as_inventory) {
                    $this->inventoryService->receiveStock(
                        $companyId,
                        $line->product_id,
                        $sale->branch_id,
                        (float) $line->quantity,
                        (float) $line->cost_of_goods / max((float) $line->quantity, 1),
                        'pos_void',
                        $sale->id,
                        now()->toDateString()
                    );
                }
            }

            // 2. Reverse the journal entry (if posted)
            if ($sale->journal_entry_id) {
                $this->reverseJournalEntry($sale, $companyId, $userId);
            }

            // 3. Update status
            $sale->update(['status' => PosSale::STATUS_VOIDED]);

            AuditLog::log(
                $companyId,
                $userId,
                PosSale::class,
                $sale->id,
                'pos.sale.voided',
                ['status' => 'posted'],
                ['status' => 'voided'],
                "Voided sale {$sale->sale_number}"
            );

            return $sale->fresh();
        });
    }

    private function reverseJournalEntry(PosSale $sale, int $companyId, int $userId): void
    {
        $originalEntry = $sale->journalEntry;
        if (!$originalEntry) return;

        // Create a reversing entry
        $lines = $originalEntry->lines()->get()->map(fn ($line) => [
            'account_id' => $line->account_id,
            'debit' => $line->credit,
            'credit' => $line->debit,
            'description' => "Reversal: {$line->description}",
        ])->toArray();

        $this->postingEngine->post([
            'company_id' => $companyId,
            'date' => now()->toDateString(),
            'reference' => "VOID-{$sale->sale_number}",
            'memo' => "Reversal for voided sale {$sale->sale_number}",
            'source_module' => 'pos',
            'created_by' => $userId,
            'lines' => $lines,
        ]);
    }
}
