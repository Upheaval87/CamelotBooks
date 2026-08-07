<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Company;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use App\Services\Accounting\GoodsReceivedNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillServicePoBackedTest extends TestCase
{
    use RefreshDatabase;

    protected BillService $service;
    protected GoodsReceivedNoteService $grnService;
    protected Company $company;
    protected Account $apAccount;
    protected Account $expenseAccount;
    protected Account $taxReceivableAccount;
    protected Account $accruedPurchasesAccount;
    protected Account $ppvAccount;
    protected Account $inventoryAssetAccount;
    protected Vendor $vendor;
    protected Product $product;
    protected AccountingPeriod $period;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BillService::class);
        $this->grnService = app(GoodsReceivedNoteService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->apAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->taxReceivableAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1150',
            'name' => 'Tax Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->accruedPurchasesAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2150',
            'name' => 'Accrued Purchases',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->ppvAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6800',
            'name' => 'Purchase Price Variance',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->inventoryAssetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1200',
            'name' => 'Inventory Asset',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->salesRevenueAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Widget Supplier',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget A',
            'sku' => 'WGT-001',
            'type' => 'inventory',
            'sales_price' => 20.00,
            'income_account_id' => $this->salesRevenueAccount->id,
            'tracked_as_inventory' => true,
            'inventory_asset_account_id' => $this->inventoryAssetAccount->id,
            'is_active' => true,
        ]);
    }

    protected function createPoBackedSetup(float $grnUnitCost, float $billUnitPrice): array
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'po_number' => 'PO-2026-0001',
            'date' => '2026-01-10',
            'status' => 'sent',
            'created_by' => $this->userId,
        ]);

        $poLine = PurchaseOrderLine::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'description' => 'Widget A',
            'quantity' => 10,
            'unit_price' => $billUnitPrice,
            'amount' => 10 * $billUnitPrice,
            'expense_account_id' => $this->expenseAccount->id,
        ]);

        $grn = $this->grnService->create([
            'company_id' => $this->company->id,
            'purchase_order_id' => $po->id,
            'vendor_id' => $this->vendor->id,
            'date' => '2026-01-15',
            'lines' => [
                [
                    'purchase_order_line_id' => $poLine->id,
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity_ordered' => 10,
                    'quantity_received' => 10,
                    'unit_cost' => $grnUnitCost,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $grn = $this->grnService->post($grn, $this->userId);

        $grnLine = $grn->lines()->first();

        return compact('po', 'poLine', 'grn', 'grnLine');
    }

    public function test_po_backed_bill_posts_dr_accrued_cr_ap(): void
    {
        $setup = $this->createPoBackedSetup(10.00, 10.00);

        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $setup['po']->id,
            'grn_id' => $setup['grn']->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity' => 10,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'purchase_order_line_id' => $setup['poLine']->id,
                ],
            ],
        ], $this->userId);

        $bill = $this->service->post($bill, $this->userId);

        $this->assertEquals(Bill::STATUS_APPROVED, $bill->status);
        $this->assertNotNull($bill->journal_entry_id);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        // DR AccruedPurchases 100, CR AP 100 (no PPV since amounts match)
        $accruedLine = $lines->firstWhere('account_id', $this->accruedPurchasesAccount->id);
        $this->assertNotNull($accruedLine);
        $this->assertEquals(100.00, (float) $accruedLine->debit);
        $this->assertEquals(0, (float) $accruedLine->credit);

        $apLine = $lines->firstWhere('account_id', $this->apAccount->id);
        $this->assertNotNull($apLine);
        $this->assertEquals(100.00, (float) $apLine->credit);

        $ppvLine = $lines->firstWhere('account_id', $this->ppvAccount->id);
        $this->assertNull($ppvLine);

        // No inventory asset line (stock already received via GRN)
        $invLine = $lines->firstWhere('account_id', $this->inventoryAssetAccount->id);
        $this->assertNull($invLine);

        // Balanced
        $this->assertEqualsWithDelta($lines->sum('debit'), $lines->sum('credit'), 0.01);
    }

    public function test_po_backed_bill_with_unfavorable_ppv(): void
    {
        // GRN at $10, bill at $12 -> unfavorable PPV of $20
        $setup = $this->createPoBackedSetup(10.00, 12.00);

        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $setup['po']->id,
            'grn_id' => $setup['grn']->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity' => 10,
                    'unit_price' => 12.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'purchase_order_line_id' => $setup['poLine']->id,
                ],
            ],
        ], $this->userId);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        // DR AccruedPurchases 100 (from GRN accrued total)
        $accruedLine = $lines->firstWhere('account_id', $this->accruedPurchasesAccount->id);
        $this->assertNotNull($accruedLine);
        $this->assertEquals(100.00, (float) $accruedLine->debit);

        // CR AP 120 (bill amount)
        $apLine = $lines->firstWhere('account_id', $this->apAccount->id);
        $this->assertNotNull($apLine);
        $this->assertEquals(120.00, (float) $apLine->credit);

        // Unfavorable PPV: bill (120) - accrued (100) = +20, so DR PPV 20
        $ppvLine = $lines->firstWhere('account_id', $this->ppvAccount->id);
        $this->assertNotNull($ppvLine);
        $this->assertEquals(20.00, (float) $ppvLine->debit);
        $this->assertEquals(0, (float) $ppvLine->credit);

        $this->assertEqualsWithDelta($lines->sum('debit'), $lines->sum('credit'), 0.01);
    }

    public function test_po_backed_bill_with_favorable_ppv(): void
    {
        // GRN at $10, bill at $8 -> favorable PPV of $20
        $setup = $this->createPoBackedSetup(10.00, 8.00);

        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $setup['po']->id,
            'grn_id' => $setup['grn']->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity' => 10,
                    'unit_price' => 8.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'purchase_order_line_id' => $setup['poLine']->id,
                ],
            ],
        ], $this->userId);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        // DR AccruedPurchases 100 (from GRN accrued total)
        $accruedLine = $lines->firstWhere('account_id', $this->accruedPurchasesAccount->id);
        $this->assertNotNull($accruedLine);
        $this->assertEquals(100.00, (float) $accruedLine->debit);

        // CR AP 80 (bill amount)
        $apLine = $lines->firstWhere('account_id', $this->apAccount->id);
        $this->assertNotNull($apLine);
        $this->assertEquals(80.00, (float) $apLine->credit);

        // Favorable PPV: accrued (100) - bill (80) = +20, so CR PPV 20
        $ppvLine = $lines->firstWhere('account_id', $this->ppvAccount->id);
        $this->assertNotNull($ppvLine);
        $this->assertEquals(0, (float) $ppvLine->debit);
        $this->assertEquals(20.00, (float) $ppvLine->credit);

        $this->assertEqualsWithDelta($lines->sum('debit'), $lines->sum('credit'), 0.01);
    }

    public function test_po_backed_bill_does_not_create_cost_layers(): void
    {
        $setup = $this->createPoBackedSetup(10.00, 10.00);

        // Verify GRN created cost layers via service
        $grnLayers = InventoryCostLayer::where('source_type', 'grn')->count();
        $this->assertEquals(1, $grnLayers);

        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $setup['po']->id,
            'grn_id' => $setup['grn']->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity' => 10,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'purchase_order_line_id' => $setup['poLine']->id,
                ],
            ],
        ], $this->userId);

        $bill = $this->service->post($bill, $this->userId);

        // Bill should NOT create any additional cost layers
        $this->assertEquals(1, InventoryCostLayer::where('source_type', 'grn')->count());
        $this->assertEquals(0, InventoryCostLayer::where('source_type', 'bill')->count());
    }

    public function test_non_po_bill_still_receives_stock(): void
    {
        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A direct',
                    'quantity' => 5,
                    'unit_price' => 15.00,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        // Should DR inventory asset (not accrued purchases)
        $invLine = $lines->firstWhere('account_id', $this->inventoryAssetAccount->id);
        $this->assertNotNull($invLine);
        $this->assertEquals(75.00, (float) $invLine->debit);

        // No accrued purchases line
        $accruedLine = $lines->firstWhere('account_id', $this->accruedPurchasesAccount->id);
        $this->assertNull($accruedLine);

        // Should create cost layer
        $this->assertEquals(1, InventoryCostLayer::where('source_type', 'bill')->count());
    }

    public function test_po_backed_bill_updates_po_quantity_billed(): void
    {
        $setup = $this->createPoBackedSetup(10.00, 10.00);

        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $setup['po']->id,
            'grn_id' => $setup['grn']->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity' => 10,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'purchase_order_line_id' => $setup['poLine']->id,
                ],
            ],
        ], $this->userId);

        $this->assertEquals(0, (float) $setup['poLine']->fresh()->quantity_billed);

        $bill = $this->service->post($bill, $this->userId);

        $this->assertEquals(10, (float) $setup['poLine']->fresh()->quantity_billed);
    }

    public function test_po_backed_bill_with_charges_creates_balanced_je(): void
    {
        $setup = $this->createPoBackedSetup(10.00, 10.00);

        $bill = $this->service->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $setup['po']->id,
            'grn_id' => $setup['grn']->id,
            'bill_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'freight_charges' => 25,
            'insurance_charges' => 10,
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Widget A',
                    'quantity' => 10,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'purchase_order_line_id' => $setup['poLine']->id,
                ],
            ],
        ], $this->userId);

        $this->assertEquals(135.00, (float) $bill->amount);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        // DR AccruedPurchases 100, DR expense 35 (charges), CR AP 135
        $accruedLine = $lines->firstWhere('account_id', $this->accruedPurchasesAccount->id);
        $this->assertEquals(100.00, (float) $accruedLine->debit);

        $expenseCharges = $lines->where('account_id', $this->expenseAccount->id);
        $this->assertEquals(35.00, round($expenseCharges->sum('debit'), 2));

        $apCredit = $lines->where('account_id', $this->apAccount->id)->sum('credit');
        $this->assertEquals(135.00, (float) $apCredit);

        $this->assertEqualsWithDelta($lines->sum('debit'), $lines->sum('credit'), 0.01);
    }
}
