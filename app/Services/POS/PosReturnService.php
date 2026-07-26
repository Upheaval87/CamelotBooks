<?php

namespace App\Services\POS;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\PosReturn;
use App\Models\PosReturnLine;
use App\Models\PosSale;
use App\Models\Product;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosReturnService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private InventoryService $inventoryService,
        private NumberingSequenceService $numberingService
    ) {}

    public function processReturn(array $data, int $userId): PosReturn
    {
        $this->validateReturnData($data);

        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];
            $sale = PosSale::where('company_id', $companyId)
                ->with(['lines', 'payments.paymentMethod'])
                ->findOrFail($data['pos_sale_id']);

            if (!$sale->isPosted()) {
                throw new InvalidArgumentException('Can only return items from a posted sale.');
            }

            $returnNumber = $this->numberingService->getNextNumber($companyId, 'pos_return');

            $return = PosReturn::create([
                'company_id' => $companyId,
                'pos_sale_id' => $sale->id,
                'terminal_id' => $sale->terminal_id,
                'customer_id' => $sale->customer_id,
                'branch_id' => $sale->branch_id,
                'cost_center_id' => $sale->cost_center_id,
                'return_number' => $returnNumber,
                'date' => $data['date'] ?? now()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'status' => PosReturn::STATUS_DRAFT,
                'created_by' => $userId,
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            $totalCOGS = 0;

            foreach ($data['lines'] as $lineData) {
                $saleLine = $sale->lines()
                    ->where('id', $lineData['pos_sale_line_id'])
                    ->firstOrFail();

                $product = Product::where('id', $saleLine->product_id)
                    ->where('company_id', $companyId)
                    ->firstOrFail();

                $qtyReturning = (float) $lineData['quantity_returned'];
                $qtySold = (float) $saleLine->quantity;

                if ($qtyReturning <= 0) {
                    throw new InvalidArgumentException("Return quantity must be positive for line: {$saleLine->id}.");
                }
                if ($qtyReturning > $qtySold) {
                    throw new InvalidArgumentException(
                        "Cannot return {$qtyReturning} of product \"{$product->name}\". Only {$qtySold} were sold."
                    );
                }

                $unitPrice = (float) $saleLine->unit_price;
                $taxRate = (float) $saleLine->tax_rate;
                $lineSubtotal = round($qtyReturning * $unitPrice, 2);
                $lineTaxAmount = round($lineSubtotal * ($taxRate / 100), 2);
                $lineTotal = round($lineSubtotal + $lineTaxAmount, 2);

                $costOfGoods = null;
                if ($product->tracked_as_inventory && $qtyReturning > 0) {
                    $unitCost = $saleLine->cost_of_goods > 0
                        ? round($saleLine->cost_of_goods / $saleLine->quantity, 4)
                        : 0;
                    $costOfGoods = round($qtyReturning * $unitCost, 2);

                    if ($costOfGoods > 0) {
                        $this->inventoryService->receiveStock(
                            $companyId,
                            $product->id,
                            $sale->branch_id,
                            $qtyReturning,
                            $unitCost,
                            'pos_return',
                            $return->id,
                            $return->date
                        );
                    }

                    $totalCOGS += $costOfGoods;
                }

                PosReturnLine::create([
                    'pos_return_id' => $return->id,
                    'pos_sale_line_id' => $saleLine->id,
                    'product_id' => $product->id,
                    'quantity_returned' => $qtyReturning,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTaxAmount,
                    'line_total' => $lineTotal,
                    'cost_of_goods' => $costOfGoods,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTaxAmount;
            }

            $total = round($subtotal + $taxTotal, 2);

            $return->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            $journalEntry = $this->postReturnEntry($return, $sale, $companyId, $userId);

            $return->update([
                'status' => PosReturn::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            AuditLog::log(
                $companyId,
                $userId,
                PosReturn::class,
                $return->id,
                'pos.return.created',
                null,
                [
                    'return_number' => $return->return_number,
                    'sale_number' => $sale->sale_number,
                    'total' => $return->total,
                    'line_count' => $return->lines()->count(),
                ],
                "POS Return {$return->return_number} for Sale {$sale->sale_number} – $" . number_format($return->total, 2)
            );

            return $return->fresh(['lines.product', 'sale', 'journalEntry.lines.account']);
        });
    }

    private function postReturnEntry(PosReturn $return, PosSale $sale, int $companyId, int $userId): JournalEntry
    {
        $returnLines = $return->lines()->with('product')->get();
        $payments = $sale->payments()->with('paymentMethod')->get();

        $taxPayable = Account::where('company_id', $companyId)->where('code', '2300')->first();
        $cogsAccount = Account::where('company_id', $companyId)->where('code', '5000')->first();
        $invAssetAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();
        $defaultRevenue = Account::where('company_id', $companyId)->where('code', '4000')->first();

        $jeLines = [];

        // Refund: credit each clearing account proportionally to original payment
        $refundTotal = $return->total;
        $originalTotal = $sale->total;
        $paymentCredits = [];

        foreach ($payments as $payment) {
            $proportion = $originalTotal > 0 ? $payment->amount / $originalTotal : 0;
            $refundAmount = round($refundTotal * $proportion, 2);

            if ($refundAmount <= 0) {
                continue;
            }

            $clearingAccountId = $payment->paymentMethod->clearing_account_id;
            $existingKey = null;
            foreach ($paymentCredits as $idx => $pc) {
                if ($pc['account_id'] === $clearingAccountId) {
                    $existingKey = $idx;
                    break;
                }
            }
            if ($existingKey !== null) {
                $paymentCredits[$existingKey]['credit'] = round($paymentCredits[$existingKey]['credit'] + $refundAmount, 2);
            } else {
                $paymentCredits[] = [
                    'account_id' => $clearingAccountId,
                    'credit' => $refundAmount,
                    'description' => "POS Return {$return->return_number} – {$payment->paymentMethod->name}",
                ];
            }
        }

        foreach ($paymentCredits as $pc) {
            $jeLines[] = [
                'account_id' => $pc['account_id'],
                'debit' => 0,
                'credit' => $pc['credit'],
                'description' => $pc['description'],
            ];
        }

        // Debit: revenue per line (reverses original credit)
        foreach ($returnLines as $line) {
            $revenueAccountId = $line->product->income_account_id ?? $defaultRevenue?->id;
            if (!$revenueAccountId) {
                throw new InvalidArgumentException("No revenue account found for product: {$line->product->name}");
            }

            $lineNet = round($line->line_total - $line->tax_amount, 2);

            $existingKey = null;
            foreach ($jeLines as $idx => $jl) {
                if ($jl['account_id'] === $revenueAccountId && $jl['debit'] > 0 && empty($jl['_tax'])) {
                    $existingKey = $idx;
                    break;
                }
            }
            if ($existingKey !== null) {
                $jeLines[$existingKey]['debit'] = round($jeLines[$existingKey]['debit'] + $lineNet, 2);
            } else {
                $jeLines[] = [
                    'account_id' => $revenueAccountId,
                    'debit' => $lineNet,
                    'credit' => 0,
                    'description' => "POS Return {$return->return_number} – {$line->product->name}",
                ];
            }

            // Debit: tax payable (reverses original credit)
            if ($line->tax_amount > 0 && $taxPayable) {
                $jeLines[] = [
                    'account_id' => $taxPayable->id,
                    'debit' => $line->tax_amount,
                    'credit' => 0,
                    'description' => "POS Return {$return->return_number} – Tax reversal",
                    '_tax' => true,
                ];
            }

            // CR COGS / DR Inventory (reverses original DR COGS / CR Inventory)
            if ($line->product->tracked_as_inventory && $line->cost_of_goods > 0) {
                if ($cogsAccount) {
                    $jeLines[] = [
                        'account_id' => $cogsAccount->id,
                        'debit' => 0,
                        'credit' => $line->cost_of_goods,
                        'description' => "POS Return {$return->return_number} – COGS reversal",
                    ];
                }
                if ($invAssetAccount) {
                    $jeLines[] = [
                        'account_id' => $invAssetAccount->id,
                        'debit' => $line->cost_of_goods,
                        'credit' => 0,
                        'description' => "POS Return {$return->return_number} – Inventory restoration",
                    ];
                }
            }
        }

        return $this->postingEngine->post([
            'company_id' => $companyId,
            'date' => $return->date,
            'reference' => "POS-RETURN-{$return->return_number}",
            'memo' => "POS Return {$return->return_number} for Sale {$sale->sale_number}",
            'lines' => $jeLines,
            'created_by' => $userId,
            'source_module' => 'pos',
        ]);
    }

    private function validateReturnData(array $data): void
    {
        if (empty($data['company_id'])) {
            throw new InvalidArgumentException('company_id is required.');
        }
        if (empty($data['pos_sale_id'])) {
            throw new InvalidArgumentException('pos_sale_id is required.');
        }
        if (empty($data['lines']) || !is_array($data['lines']) || count($data['lines']) === 0) {
            throw new InvalidArgumentException('At least one return line is required.');
        }

        foreach ($data['lines'] as $i => $line) {
            if (empty($line['pos_sale_line_id'])) {
                throw new InvalidArgumentException("Line {$i}: pos_sale_line_id is required.");
            }
            if (empty($line['quantity_returned']) || (float) $line['quantity_returned'] <= 0) {
                throw new InvalidArgumentException("Line {$i}: quantity_returned must be positive.");
            }
        }
    }
}
