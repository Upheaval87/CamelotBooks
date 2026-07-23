<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use App\Services\Accounting\InvoiceService;
use App\Services\Accounting\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private int $userId;
    private AccountingPeriod $period;
    private Account $apAccount;
    private Account $arAccount;
    private Account $expenseAccount;
    private Account $incomeAccount;
    private Account $invAssetAccount;
    private Account $cogsAccount;
    private Account $taxPayableAccount;
    private Account $taxReceivableAccount;
    private Product $inventoryProduct;
    private Product $serviceProduct;
    private Vendor $vendor;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Inventory Integration Co',
            'company_code' => 'IICO',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'allow_negative_stock' => false,
        ]);
        $user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

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

        $this->arAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
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

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->invAssetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1200',
            'name' => 'Inventory',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->cogsAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'Cost of Goods Sold',
            'type' => 'expense',
            'sub_type' => 'cost_of_goods_sold',
            'is_active' => true,
        ]);

        $this->taxPayableAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2300',
            'name' => 'Sales Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
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

        $this->inventoryProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WDG-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 25.00,
            'purchase_price' => 10.00,
            'income_account_id' => $this->incomeAccount->id,
            'expense_account_id' => $this->cogsAccount->id,
            'inventory_asset_account_id' => $this->invAssetAccount->id,
            'is_taxable' => false,
            'is_active' => true,
        ]);

        $this->serviceProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Consulting',
            'sku' => 'SRV-001',
            'type' => 'service',
            'tracked_as_inventory' => false,
            'sales_price' => 100.00,
            'income_account_id' => $this->incomeAccount->id,
            'is_taxable' => false,
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Supplier',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Buyer',
            'is_active' => true,
        ]);
    }

    public function test_bill_with_inventory_item_receives_stock(): void
    {
        $billService = app(BillService::class);
        $inventoryService = app(InventoryService::class);

        $bill = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Widget purchase',
                    'quantity' => 50,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);

        $billService->post($bill, $this->userId);

        $onHand = $inventoryService->getQuantityOnHand(
            $this->company->id,
            $this->inventoryProduct->id
        );

        $this->assertEquals(50, $onHand);
    }

    public function test_bill_with_non_inventory_item_does_not_create_cost_layers(): void
    {
        $billService = app(BillService::class);
        $inventoryService = app(InventoryService::class);

        $bill = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'lines' => [
                [
                    'product_id' => $this->serviceProduct->id,
                    'description' => 'Consulting service',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $billService->post($bill, $this->userId);

        $onHand = $inventoryService->getQuantityOnHand(
            $this->company->id,
            $this->serviceProduct->id
        );

        $this->assertEquals(0, $onHand);
    }

    public function test_invoice_with_inventory_item_posts_cogs_and_inventory_asset_lines(): void
    {
        $billService = app(BillService::class);
        $invoiceService = app(InvoiceService::class);
        $inventoryService = app(InventoryService::class);

        $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Initial stock',
                    'quantity' => 100,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);

        $bill = Bill::latest()->first();
        $billService->post($bill, $this->userId);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Widget sale',
                    'quantity' => 20,
                    'unit_price' => 25.00,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $invoiceService->post($invoice, $this->userId);

        $onHand = $inventoryService->getQuantityOnHand(
            $this->company->id,
            $this->inventoryProduct->id
        );
        $this->assertEquals(80, $onHand);

        $journalEntry = $invoice->fresh()->journalEntry;
        $this->assertNotNull($journalEntry);

        $lines = $journalEntry->lines()->get();

        $cogsLines = $lines->where('account_id', $this->cogsAccount->id);
        $this->assertCount(1, $cogsLines);
        $this->assertEquals(200.00, $cogsLines->first()->debit);

        $invAssetLines = $lines->where('account_id', $this->invAssetAccount->id);
        $this->assertCount(1, $invAssetLines);
        $this->assertEquals(200.00, $invAssetLines->first()->credit);
    }

    public function test_invoice_oversell_prevention(): void
    {
        $billService = app(BillService::class);
        $invoiceService = app(InvoiceService::class);

        $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Stock',
                    'quantity' => 10,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);

        $bill = Bill::latest()->first();
        $billService->post($bill, $this->userId);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Oversell attempt',
                    'quantity' => 20,
                    'unit_price' => 25.00,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $invoiceService->post($invoice, $this->userId);
    }

    public function test_fifo_cost_from_multiple_bills(): void
    {
        $billService = app(BillService::class);
        $invoiceService = app(InvoiceService::class);
        $inventoryService = app(InventoryService::class);

        $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'First batch',
                    'quantity' => 10,
                    'unit_price' => 8.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);

        $bill1 = Bill::latest()->first();
        $billService->post($bill1, $this->userId);

        $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-01',
            'due_date' => '2026-03-01',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Second batch',
                    'quantity' => 10,
                    'unit_price' => 12.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);

        $bill2 = Bill::where('company_id', $this->company->id)->latest()->skip(1)->first();
        $billService->post($bill2, $this->userId);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Sale spanning batches',
                    'quantity' => 15,
                    'unit_price' => 25.00,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $invoiceService->post($invoice, $this->userId);

        $onHand = $inventoryService->getQuantityOnHand(
            $this->company->id,
            $this->inventoryProduct->id
        );
        $this->assertEquals(5, $onHand);

        $journalEntry = $invoice->fresh()->journalEntry;
        $cogsLine = $journalEntry->lines()
            ->where('account_id', $this->cogsAccount->id)
            ->first();

        $this->assertEquals(140.00, $cogsLine->debit);
    }

    public function test_inventory_valuation_reconciles_to_gl_asset_balance(): void
    {
        $billService = app(BillService::class);
        $invoiceService = app(InvoiceService::class);
        $inventoryService = app(InventoryService::class);

        $bill1 = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'First batch',
                    'quantity' => 100,
                    'unit_price' => 8.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);
        $billService->post($bill1, $this->userId);

        $bill2 = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Second batch',
                    'quantity' => 50,
                    'unit_price' => 12.00,
                    'expense_account_id' => $this->cogsAccount->id,
                ],
            ],
        ], $this->userId);
        $billService->post($bill2, $this->userId);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-20',
            'lines' => [
                [
                    'product_id' => $this->inventoryProduct->id,
                    'description' => 'Sale',
                    'quantity' => 30,
                    'unit_price' => 25.00,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);
        $invoiceService->post($invoice, $this->userId);

        $valuation = $inventoryService->getTotalInventoryAssetValue($this->company->id);
        $glBalance = (float) $this->invAssetAccount->fresh()->current_balance;

        $this->assertEqualsWithDelta($valuation, $glBalance, 0.01);
    }
}
