<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\Accounting\InventoryService;
use App\Services\FeatureManagement;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;
    private PosCashierSession $session;
    private Product $product;
    private PosPaymentMethod $cashMethod;
    private Account $revenueAccount;
    private Account $taxPayable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'POS Sale Co',
            'company_code' => 'PSC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'pos');
        FeatureManagement::enable($this->company->id, 'inventory');
        PosSetupService::seedDefaultsForCompany($this->company->id);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        // Numbering sequence for POS sales
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'pos_sale',
            'prefix' => 'POS-',
            'padding_width' => 5,
            'reset_policy' => 'never',
            'next_number' => 1,
            'is_active' => true,
        ]);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Front Counter',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        $this->session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $this->revenueAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        $this->taxPayable = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2300'],
            ['name' => 'Sales Tax Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '5000'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1200'],
            ['name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WGT-001',
            'type' => 'goods',
            'tracked_as_inventory' => false,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 10.00,
            'is_taxable' => true,
            'is_active' => true,
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $this->cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();
    }

    private function baseSaleData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $this->session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 22.00],
            ],
        ], $overrides);
    }

    // =============================================
    // BASIC CHECKOUT
    // =============================================

    public function test_checkout_creates_sale(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $this->assertNotNull($sale);
        $this->assertEquals(PosSale::STATUS_POSTED, $sale->status);
        $this->assertEquals(20.00, $sale->subtotal);
        $this->assertEquals(2.00, $sale->tax_total);
        $this->assertEquals(22.00, $sale->total);
    }

    public function test_checkout_creates_sale_number(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $this->assertStringStartsWith('POS-', $sale->sale_number);
        $this->assertDatabaseHas('pos_sales', ['sale_number' => $sale->sale_number]);
    }

    public function test_checkout_creates_line_items(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $this->assertCount(1, $sale->lines);
        $line = $sale->lines->first();
        $this->assertEquals($this->product->id, $line->product_id);
        $this->assertEquals(2, $line->quantity);
        $this->assertEquals(10.00, $line->unit_price);
        $this->assertEquals(10.00, $line->tax_rate);
        $this->assertEquals(2.00, $line->tax_amount);
        $this->assertEquals(22.00, $line->line_total);
    }

    public function test_checkout_creates_payments(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $this->assertCount(1, $sale->payments);
        $this->assertEquals($this->cashMethod->id, $sale->payments->first()->payment_method_id);
        $this->assertEquals(22.00, $sale->payments->first()->amount);
    }

    // =============================================
    // GL POSTING
    // =============================================

    public function test_checkout_posts_journal_entry(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $this->assertNotNull($sale->journal_entry_id);

        $je = $sale->journalEntry;
        $this->assertEquals('posted', $je->status);
        $this->assertEquals($this->company->id, $je->company_id);
        $this->assertEquals('pos', $je->source_module);

        $lines = $je->lines;
        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');
        $this->assertEquals(round($totalDebit, 2), round($totalCredit, 2));
    }

    public function test_cash_payment_debits_clearing_account(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $cashInDrawer = Account::where('company_id', $this->company->id)->where('code', '1060')->first();
        $lines = $sale->journalEntry->lines;

        $cashLine = $lines->where('account_id', $cashInDrawer->id)->first();
        $this->assertNotNull($cashLine);
        $this->assertEquals(22.00, $cashLine->debit);
    }

    public function test_revenue_account_is_credited(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $lines = $sale->journalEntry->lines;
        $revenueLine = $lines->where('account_id', $this->revenueAccount->id)->first();
        $this->assertNotNull($revenueLine);
        $this->assertEquals(20.00, $revenueLine->credit);
    }

    public function test_tax_payable_is_credited(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $lines = $sale->journalEntry->lines;
        $taxLine = $lines->where('account_id', $this->taxPayable->id)->first();
        $this->assertNotNull($taxLine);
        $this->assertEquals(2.00, $taxLine->credit);
    }

    // =============================================
    // MULTIPLE PAYMENTS
    // =============================================

    public function test_split_payments(): void
    {
        $cardMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Card')->first();

        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData([
                'payments' => [
                    ['payment_method_id' => $this->cashMethod->id, 'amount' => 10.00],
                    ['payment_method_id' => $cardMethod->id, 'amount' => 12.00],
                ],
            ]),
            $this->user->id
        );

        $this->assertCount(2, $sale->payments);
        $this->assertEquals(22.00, $sale->total);
        $this->assertEquals(PosSale::STATUS_POSTED, $sale->status);

        $lines = $sale->journalEntry->lines;
        $cashInDrawer = Account::where('company_id', $this->company->id)->where('code', '1060')->first();
        $cardClearing = Account::where('company_id', $this->company->id)->where('code', '1070')->first();

        $this->assertEquals(10.00, $lines->where('account_id', $cashInDrawer->id)->first()->debit);
        $this->assertEquals(12.00, $lines->where('account_id', $cardClearing->id)->first()->debit);
    }

    // =============================================
    // DISCOUNTS
    // =============================================

    public function test_line_discount(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData([
                'lines' => [
                    ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10.00, 'discount_amount' => 5.00, 'tax_rate' => 10.00],
                ],
                'payments' => [
                    ['payment_method_id' => $this->cashMethod->id, 'amount' => 16.50],
                ],
            ]),
            $this->user->id
        );

        $this->assertEquals(5.00, $sale->discount_total);
        $this->assertEquals(20.00, $sale->subtotal);
        $this->assertEquals(1.50, $sale->tax_total);
        $this->assertEquals(16.50, $sale->total);
    }

    // =============================================
    // INVENTORY
    // =============================================

    public function test_tracked_inventory_posts_cogs(): void
    {
        $inventoryProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Tracked Widget',
            'sku' => 'TW-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 25.00,
            'purchase_price' => 12.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'income_account_id' => $this->revenueAccount->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
            'inventory_asset_account_id' => Account::where('company_id', $this->company->id)->where('code', '1200')->first()->id,
        ]);

        // Receive stock first
        app(InventoryService::class)->receiveStock(
            $this->company->id,
            $inventoryProduct->id,
            null,
            10,
            12.00,
            'purchase_order',
            1,
            now()->toDateString()
        );

        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData([
                'lines' => [
                    ['product_id' => $inventoryProduct->id, 'quantity' => 3, 'unit_price' => 25.00, 'tax_rate' => 0],
                ],
                'payments' => [
                    ['payment_method_id' => $this->cashMethod->id, 'amount' => 75.00],
                ],
            ]),
            $this->user->id
        );

        $cogsAccount = Account::where('company_id', $this->company->id)->where('code', '5000')->first();
        $invAsset = Account::where('company_id', $this->company->id)->where('code', '1200')->first();

        $lines = $sale->journalEntry->lines;
        $cogsLine = $lines->where('account_id', $cogsAccount->id)->first();
        $invLine = $lines->where('account_id', $invAsset->id)->first();

        $this->assertNotNull($cogsLine);
        $this->assertNotNull($invLine);
        $this->assertEquals(36.00, $cogsLine->debit);
        $this->assertEquals(36.00, $invLine->credit);

        $line = $sale->lines->first();
        $this->assertEquals(36.00, $line->cost_of_goods);
    }

    // =============================================
    // VALIDATION
    // =============================================

    public function test_payment_must_cover_total(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData([
                'payments' => [
                    ['payment_method_id' => $this->cashMethod->id, 'amount' => 10.00],
                ],
            ]),
            $this->user->id
        );
    }

    public function test_requires_lines(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(['lines' => []]),
            $this->user->id
        );
    }

    public function test_requires_payments(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(['payments' => []]),
            $this->user->id
        );
    }

    public function test_cannot_sell_on_closed_session(): void
    {
        // Use a separate terminal to avoid conflict with setUp's open session
        $terminal2 = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Terminal 2',
            'identifier' => 'T2',
            'is_active' => true,
        ]);

        $closedSession = app(TillSessionService::class)->openTill(
            $this->company->id,
            $terminal2->id,
            $this->user->id,
            100.00
        );
        app(TillSessionService::class)->closeTill($closedSession, 100.00);

        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(['cashier_session_id' => $closedSession->fresh()->id]),
            $this->user->id
        );
    }

    // =============================================
    // CONTROLLER
    // =============================================

    public function test_checkout_view_loads(): void
    {
        $this->get(route('pos.sales.checkout'))->assertOk();
    }

    public function test_store_creates_sale(): void
    {
        $this->postJson(route('pos.sales.store'), $this->baseSaleData())
            ->assertOk()
            ->assertJsonStructure(['success', 'sale_id', 'sale_number', 'total']);

        $this->assertDatabaseCount('pos_sales', 1);
    }

    public function test_receipt_loads(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $this->get(route('pos.sales.receipt', $sale))->assertOk();
    }

    // =============================================
    // ISOLATION
    // =============================================

    public function test_company_isolation(): void
    {
        $sale = app(\App\Services\POS\PosSaleService::class)->checkout(
            $this->baseSaleData(),
            $this->user->id
        );

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->assertNull(PosSale::where('company_id', $otherCompany->id)->first());
    }
}
