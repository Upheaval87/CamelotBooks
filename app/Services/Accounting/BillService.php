<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Accounting\ForeignCurrencyService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillService
{
    protected JournalPostingEngine $postingEngine;
    protected ForeignCurrencyService $fxService;
    protected InventoryService $inventoryService;

    public function __construct(JournalPostingEngine $postingEngine, ForeignCurrencyService $fxService, InventoryService $inventoryService)
    {
        $this->postingEngine = $postingEngine;
        $this->fxService = $fxService;
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data, int $userId): Bill
    {
        $companyId = $data['company_id'];

        $this->validateVendor($companyId, $data['vendor_id']);

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one bill line is required.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $billNumber = $this->generateBillNumber($companyId);

            $bill = Bill::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'grn_id' => $data['grn_id'] ?? null,
                'bill_number' => $billNumber,
                'internal_number' => $data['internal_number'] ?? null,
                'bill_date' => $data['bill_date'],
                'due_date' => $data['due_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => Bill::STATUS_DRAFT,
                'amount' => 0,
                'amount_paid' => 0,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->createLine($bill, $lineData, $companyId);
            }

            $this->updateBillAmount($bill);

            $this->logBillAction($bill, 'created', null, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function update(Bill $bill, array $data, int $userId): Bill
    {
        if ($bill->status !== Bill::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft bills can be updated.');
        }

        $companyId = $bill->company_id;

        if (isset($data['vendor_id'])) {
            $this->validateVendor($companyId, $data['vendor_id']);
        }

        return DB::transaction(function () use ($bill, $data, $userId, $companyId) {
            $oldValues = $bill->toArray();

            $bill->update([
                'vendor_id' => $data['vendor_id'] ?? $bill->vendor_id,
                'bill_date' => $data['bill_date'] ?? $bill->bill_date,
                'due_date' => $data['due_date'] ?? $bill->due_date,
                'internal_number' => $data['internal_number'] ?? $bill->internal_number,
                'reference' => $data['reference'] ?? $bill->reference,
                'memo' => $data['memo'] ?? $bill->memo,
                'branch_id' => $data['branch_id'] ?? $bill->branch_id,
                'currency' => $data['currency'] ?? $bill->currency,
            ]);

            if (isset($data['lines'])) {
                $bill->lines()->delete();

                foreach ($data['lines'] as $lineData) {
                    $this->createLine($bill, $lineData, $companyId);
                }
            }

            $this->updateBillAmount($bill);

            $this->logBillAction($bill, 'updated', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function post(Bill $bill, int $userId): Bill
    {
        if (!in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_PENDING_APPROVAL])) {
            throw new InvalidArgumentException('Only draft or pending approval bills can be posted.');
        }

        $companyId = $bill->company_id;
        $apAccount = $this->findAccountByCode($companyId, '2000');
        $taxReceivableAccount = $this->findAccountByCode($companyId, '1150');
        $isPoBacked = !is_null($bill->purchase_order_id);

        return DB::transaction(function () use ($bill, $userId, $companyId, $apAccount, $taxReceivableAccount, $isPoBacked) {
            $oldValues = $bill->toArray();

            $lines = $bill->lines()->with('product')->get();
            $jeLines = [];
            $totalDebit = 0;
            $totalCredit = 0;

            if ($isPoBacked) {
                // PO-backed bill: clear the accrual created by the GRN
                // DR Accrued Purchases (2150) for GRN total / CR AP (2000) for bill total
                // PPV (6800) absorbs any price variance
                $accruedAccount = $this->findAccountByCode($companyId, '2150');
                $ppvAccount = $this->findAccountByCode($companyId, '6800');

                $totalBillAmount = 0;
                $totalTaxAmount = 0;
                $totalLineTotal = 0;

                // CR AP for each bill line
                foreach ($lines as $line) {
                    if ($line->tax_amount > 0) {
                        $jeLines[] = [
                            'account_id' => $taxReceivableAccount->id,
                            'debit' => $line->tax_amount,
                            'credit' => 0,
                            'memo' => "Bill {$bill->bill_number} - Tax - {$line->description}",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                            'cost_center_id' => $line->cost_center_id,
                        ];
                        $totalDebit += $line->tax_amount;
                        $totalTaxAmount += $line->tax_amount;
                    }

                    $jeLines[] = [
                        'account_id' => $apAccount->id,
                        'debit' => 0,
                        'credit' => $line->line_total,
                        'memo' => "Bill {$bill->bill_number} - {$line->description}",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                        'cost_center_id' => $line->cost_center_id,
                    ];
                    $totalCredit += $line->line_total;

                    $totalBillAmount += $line->amount;
                    $totalLineTotal += $line->line_total;
                }

                // Compute accrual total from the linked GRN
                $totalAccrued = $this->computeAccrualTotal($bill, $lines);

                // DR Accrued Purchases for the full accrued amount
                $jeLines[] = [
                    'account_id' => $accruedAccount->id,
                    'debit' => $totalAccrued,
                    'credit' => 0,
                    'memo' => "Bill {$bill->bill_number} - Clear accrual",
                    'entity_type' => Bill::class,
                    'entity_id' => $bill->id,
                ];
                $totalDebit += $totalAccrued;

                // PPV = billAmount - accrued (positive = unfavorable, negative = favorable)
                $ppvVariance = round($totalBillAmount - $totalAccrued, 2);

                if (abs($ppvVariance) > 0.001) {
                    if ($ppvVariance > 0) {
                        // Bill > accrued -> debit PPV (unfavorable)
                        $jeLines[] = [
                            'account_id' => $ppvAccount->id,
                            'debit' => abs($ppvVariance),
                            'credit' => 0,
                            'memo' => "Bill {$bill->bill_number} - Purchase Price Variance",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                        ];
                        $totalDebit += abs($ppvVariance);
                    } else {
                        // Bill < accrued -> credit PPV (favorable)
                        $jeLines[] = [
                            'account_id' => $ppvAccount->id,
                            'debit' => 0,
                            'credit' => abs($ppvVariance),
                            'memo' => "Bill {$bill->bill_number} - Purchase Price Variance",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                        ];
                        $totalCredit += abs($ppvVariance);
                    }
                }

                // Update PO line quantity_billed
                foreach ($lines as $line) {
                    if ($line->purchase_order_line_id) {
                        $poLine = \App\Models\PurchaseOrderLine::find($line->purchase_order_line_id);
                        if ($poLine) {
                            $poLine->increment('quantity_billed', $line->quantity);
                        }
                    }
                }

            } else {
                // Standalone bill (no PO): DR Inventory Asset or Expense / CR AP
                // and create FIFO cost layers
                foreach ($lines as $line) {
                    $debitAccountId = $line->expense_account_id;

                    if ($line->product && $line->product->tracked_as_inventory && $line->product->inventory_asset_account_id) {
                        $debitAccountId = $line->product->inventory_asset_account_id;
                    }

                    $jeLines[] = [
                        'account_id' => $debitAccountId,
                        'debit' => $line->amount,
                        'credit' => 0,
                        'memo' => "Bill {$bill->bill_number} - {$line->description}",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                        'cost_center_id' => $line->cost_center_id,
                    ];
                    $totalDebit += $line->amount;

                    if ($line->tax_amount > 0) {
                        $jeLines[] = [
                            'account_id' => $taxReceivableAccount->id,
                            'debit' => $line->tax_amount,
                            'credit' => 0,
                            'memo' => "Bill {$bill->bill_number} - Tax - {$line->description}",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                            'cost_center_id' => $line->cost_center_id,
                        ];
                        $totalDebit += $line->tax_amount;
                    }

                    $jeLines[] = [
                        'account_id' => $apAccount->id,
                        'debit' => 0,
                        'credit' => $line->line_total,
                        'memo' => "Bill {$bill->bill_number} - {$line->description}",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                        'cost_center_id' => $line->cost_center_id,
                    ];
                    $totalCredit += $line->line_total;

                    // Receive stock for non-PO bills only
                    if ($line->product && $line->product->tracked_as_inventory && $line->product_id) {
                        $qty = (float) $line->quantity;
                        if ($qty > 0) {
                            $unitCost = round($line->amount / $qty, 4);
                            $this->inventoryService->receiveStock(
                                $companyId,
                                $line->product_id,
                                $bill->branch_id,
                                $qty,
                                $unitCost,
                                'bill',
                                $bill->id,
                                $bill->bill_date->format('Y-m-d')
                            );
                        }
                    }
                }
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new InvalidArgumentException(
                    "Journal entry does not balance. Debit: " . number_format($totalDebit, 2) .
                    ", Credit: " . number_format($totalCredit, 2)
                );
            }

            $this->fxService->postBillInForeignCurrency($bill, $jeLines, $userId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $bill->bill_date->format('Y-m-d'),
                'source_module' => 'bill',
                'reference' => $bill->bill_number,
                'memo' => "Vendor bill {$bill->bill_number}",
                'lines' => $jeLines,
            ]);

            $bill->update([
                'status' => Bill::STATUS_APPROVED,
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logBillAction($bill, 'posted', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function approve(Bill $bill, int $userId): Bill
    {
        if ($bill->status !== Bill::STATUS_PENDING_APPROVAL) {
            throw new InvalidArgumentException('Only bills pending approval can be approved.');
        }

        return DB::transaction(function () use ($bill, $userId) {
            $oldValues = $bill->toArray();

            $bill->update([
                'status' => Bill::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            if (!$bill->journal_entry_id) {
                $companyId = $bill->company_id;
                $apAccount = $this->findAccountByCode($companyId, '2000');
                $taxReceivableAccount = $this->findAccountByCode($companyId, '1150');
                $isPoBacked = !is_null($bill->purchase_order_id);

                $lines = $bill->lines()->with('product')->get();
                $jeLines = [];
                $totalDebit = 0;
                $totalCredit = 0;

                if ($isPoBacked) {
                    $accruedAccount = $this->findAccountByCode($companyId, '2150');
                    $ppvAccount = $this->findAccountByCode($companyId, '6800');
                    $totalBillAmount = 0;
                    $totalLineTotal = 0;

                    // CR AP for each bill line
                    foreach ($lines as $line) {
                        if ($line->tax_amount > 0) {
                            $jeLines[] = [
                                'account_id' => $taxReceivableAccount->id,
                                'debit' => $line->tax_amount,
                                'credit' => 0,
                                'memo' => "Bill {$bill->bill_number} - Tax - {$line->description}",
                                'entity_type' => Bill::class,
                                'entity_id' => $bill->id,
                            ];
                            $totalDebit += $line->tax_amount;
                        }

                        $jeLines[] = [
                            'account_id' => $apAccount->id,
                            'debit' => 0,
                            'credit' => $line->line_total,
                            'memo' => "Bill {$bill->bill_number} - {$line->description}",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                        ];
                        $totalCredit += $line->line_total;

                        $totalBillAmount += $line->amount;
                        $totalLineTotal += $line->line_total;
                    }

                    $totalAccrued = $this->computeAccrualTotal($bill, $lines);

                    // DR Accrued Purchases for the full accrued amount
                    $jeLines[] = [
                        'account_id' => $accruedAccount->id,
                        'debit' => $totalAccrued,
                        'credit' => 0,
                        'memo' => "Bill {$bill->bill_number} - Clear accrual",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                    ];
                    $totalDebit += $totalAccrued;

                    // PPV = billAmount - accrued
                    $ppvVariance = round($totalBillAmount - $totalAccrued, 2);

                    if (abs($ppvVariance) > 0.001) {
                        if ($ppvVariance > 0) {
                            $jeLines[] = [
                                'account_id' => $ppvAccount->id,
                                'debit' => abs($ppvVariance),
                                'credit' => 0,
                                'memo' => "Bill {$bill->bill_number} - Purchase Price Variance",
                                'entity_type' => Bill::class,
                                'entity_id' => $bill->id,
                            ];
                            $totalDebit += abs($ppvVariance);
                        } else {
                            $jeLines[] = [
                                'account_id' => $ppvAccount->id,
                                'debit' => 0,
                                'credit' => abs($ppvVariance),
                                'memo' => "Bill {$bill->bill_number} - Purchase Price Variance",
                                'entity_type' => Bill::class,
                                'entity_id' => $bill->id,
                            ];
                            $totalCredit += abs($ppvVariance);
                        }
                    }

                    foreach ($lines as $line) {
                        if ($line->purchase_order_line_id) {
                            $poLine = \App\Models\PurchaseOrderLine::find($line->purchase_order_line_id);
                            if ($poLine) {
                                $poLine->increment('quantity_billed', $line->quantity);
                            }
                        }
                    }

                } else {
                    foreach ($lines as $line) {
                        $debitAccountId = $line->expense_account_id;

                        if ($line->product && $line->product->tracked_as_inventory && $line->product->inventory_asset_account_id) {
                            $debitAccountId = $line->product->inventory_asset_account_id;
                        }

                        $jeLines[] = [
                            'account_id' => $debitAccountId,
                            'debit' => $line->amount,
                            'credit' => 0,
                            'memo' => "Bill {$bill->bill_number} - {$line->description}",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                        ];

                        if ($line->tax_amount > 0) {
                            $jeLines[] = [
                                'account_id' => $taxReceivableAccount->id,
                                'debit' => $line->tax_amount,
                                'credit' => 0,
                                'memo' => "Bill {$bill->bill_number} - Tax - {$line->description}",
                                'entity_type' => Bill::class,
                                'entity_id' => $bill->id,
                            ];
                        }

                        $jeLines[] = [
                            'account_id' => $apAccount->id,
                            'debit' => 0,
                            'credit' => $line->line_total,
                            'memo' => "Bill {$bill->bill_number} - {$line->description}",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                        ];

                        if ($line->product && $line->product->tracked_as_inventory && $line->product_id) {
                            $qty = (float) $line->quantity;
                            if ($qty > 0) {
                                $unitCost = round($line->amount / $qty, 4);
                                $this->inventoryService->receiveStock(
                                    $companyId,
                                    $line->product_id,
                                    $bill->branch_id,
                                    $qty,
                                    $unitCost,
                                    'bill',
                                    $bill->id,
                                    $bill->bill_date->format('Y-m-d')
                                );
                            }
                        }
                    }
                }

                $journalEntry = $this->postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $bill->bill_date->format('Y-m-d'),
                    'source_module' => 'bill',
                    'reference' => $bill->bill_number,
                    'memo' => "Vendor bill {$bill->bill_number}",
                    'lines' => $jeLines,
                ]);

                $bill->update([
                    'journal_entry_id' => $journalEntry->id,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                ]);
            }

            $this->logBillAction($bill, 'approved', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function void(Bill $bill, string $reason, int $userId): Bill
    {
        if ($bill->status === Bill::STATUS_VOID) {
            throw new InvalidArgumentException('This bill is already voided.');
        }

        if ($bill->status === Bill::STATUS_DRAFT) {
            throw new InvalidArgumentException('Draft bills cannot be voided. Delete them instead.');
        }

        if (!$bill->journal_entry_id) {
            throw new InvalidArgumentException('This bill has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($bill, $reason, $userId) {
            $oldValues = $bill->toArray();

            $this->postingEngine->reverse($bill->journal_entry_id, $userId);

            $bill->update([
                'status' => Bill::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logBillAction($bill, 'voided', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function updateBillAmount(Bill $bill): void
    {
        $total = (float) $bill->lines()->sum('line_total');

        $bill->update(['amount' => round($total, 2)]);
    }

    public function computeLineTotals(array $lineData): array
    {
        $quantity = (float) ($lineData['quantity'] ?? 1);
        $unitPrice = (float) ($lineData['unit_price'] ?? 0);
        $discount = (float) ($lineData['discount'] ?? 0);
        $taxRate = (float) ($lineData['tax_rate'] ?? 0);

        $amount = ($quantity * $unitPrice) - $discount;
        $taxAmount = $amount * $taxRate / 100;
        $lineTotal = $amount + $taxAmount;

        return [
            'amount' => round($amount, 2),
            'tax_amount' => round($taxAmount, 2),
            'line_total' => round($lineTotal, 2),
        ];
    }

    /**
     * Compute total accrued amount from the linked GRN for PO-backed bills.
     * Looks up GRN lines matching the bill's GRN id, or falls back to PO line costs.
     */
    protected function computeAccrualTotal(Bill $bill, $billLines): float
    {
        if ($bill->grn_id) {
            $grnLines = \App\Models\GrnLine::where('goods_received_note_id', $bill->grn_id)->get();
            if ($grnLines->isNotEmpty()) {
                return (float) $grnLines->sum('total_cost');
            }
        }

        // Fallback: sum of bill line amounts (no variance)
        return (float) $billLines->sum('amount');
    }

    protected function createLine(Bill $bill, array $lineData, int $companyId): BillLine
    {
        if (isset($lineData['product_id'])) {
            $this->validateProduct($companyId, $lineData['product_id']);
        }

        $this->validateAccount($companyId, $lineData['expense_account_id']);

        $totals = $this->computeLineTotals($lineData);

        return BillLine::create([
            'bill_id' => $bill->id,
            'product_id' => $lineData['product_id'] ?? null,
            'description' => $lineData['description'],
            'quantity' => $lineData['quantity'] ?? 1,
            'unit_price' => $lineData['unit_price'],
            'discount' => $lineData['discount'] ?? 0,
            'tax_rate' => $lineData['tax_rate'] ?? 0,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'line_total' => $totals['line_total'],
            'expense_account_id' => $lineData['expense_account_id'],
            'cost_center_id' => $lineData['cost_center_id'] ?? null,
            'purchase_order_line_id' => $lineData['purchase_order_line_id'] ?? null,
        ]);
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

    protected function validateProduct(int $companyId, int $productId): void
    {
        $product = Product::where('id', $productId)
            ->where('company_id', $companyId)
            ->first();

        if (!$product) {
            throw new InvalidArgumentException("Product ID {$productId} not found for this company.");
        }
    }

    protected function validateAccount(int $companyId, int $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account ID {$accountId} not found for this company.");
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

    protected function generateBillNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'BILL-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastBill = Bill::where('company_id', $companyId)
            ->where('bill_number', 'like', $prefix . '%')
            ->orderByDesc('bill_number')
            ->first();

        if ($lastBill) {
            $lastSequence = (int) substr($lastBill->bill_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logBillAction(Bill $bill, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $bill->company_id,
            'journalable_type' => Bill::class,
            'journalable_id' => $bill->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
