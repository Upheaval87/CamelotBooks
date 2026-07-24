<?php

namespace App\Services\POS;

use App\Models\Account;
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
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosSaleService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private InventoryService $inventoryService,
        private NumberingSequenceService $numberingService
    ) {}

    public function checkout(array $data, int $userId): PosSale
    {
        $this->validateCheckoutData($data);

        return DB::transaction(function () use ($data, $userId) {
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
                $unitPrice = (float) $lineData['unit_price'];
                $discountAmount = (float) ($lineData['discount_amount'] ?? 0);
                $taxRate = (float) ($lineData['tax_rate'] ?? ($product->is_taxable ? $product->tax_rate : 0));

                $lineSubtotal = round($quantity * $unitPrice, 2);
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
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'processor_name' => $paymentData['processor_name'] ?? null,
                ]);
            }

            if (round($totalPayments, 2) < round($total, 2)) {
                throw new InvalidArgumentException(
                    'Total payments ($' . number_format($totalPayments, 2) . ') is less than sale total ($' . number_format($total, 2) . ').'
                );
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

            return $sale->fresh(['lines.product', 'payments.paymentMethod', 'journalEntry']);
        });
    }

    private function postSaleEntry(PosSale $sale, int $companyId, int $userId): JournalEntry
    {
        $lines = $sale->lines()->with('product')->get();
        $payments = $sale->payments()->with('paymentMethod')->get();

        $taxPayable = Account::where('company_id', $companyId)->where('code', '2300')->first();
        $cogsAccount = Account::where('company_id', $companyId)->where('code', '5000')->first();
        $invAssetAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();
        $defaultRevenue = Account::where('company_id', $companyId)->where('code', '4000')->first();

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
}
