<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\Accounting\InventoryService;
use App\Services\FeatureManagement;
use App\Services\POS\PosReturnService;
use App\Services\POS\PosSaleService;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReturnTest extends TestCase
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
    private Account $cogsAccount;
    private Account $invAsset;
    private PosSale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Return Test Co',
            'company_code' => 'RTC',
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

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'pos_sale',
            'prefix' => 'POS-',
            'padding_width' => 5,
            'reset_policy' => 'never',
            'next_number' => 1,
            'is_active' => true,
        ]);
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'pos_return',
            'prefix' => 'RTN-',
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
        $this->cogsAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '5000'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
        );
        $this->invAsset = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1200'],
            ['name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'tax_payable' => '2300',
            'default_revenue' => '4000',
            'default_expense' => '5000',
            'inventory_asset' => '1200',
            'undeposited_funds' => '1050',
            'cash_in_drawer' => '1060',
            'cash_shortage' => '6900',
            'cash_overage' => '7400',
        ];
        foreach ($mappingData as $key => $code) {
            if (isset($accounts[$code])) {
                \App\Models\DefaultAccountMapping::setMapping(
                    $this->company->id,
                    $key,
                    $accounts[$code]->id
                );
            }
        }

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WGT-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 10.00,
            'is_taxable' => true,
            'is_active' => true,
            'income_account_id' => $this->revenueAccount->id,
            'expense_account_id' => $this->cogsAccount->id,
            'inventory_asset_account_id' => $this->invAsset->id,
        ]);

        $this->cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();

        // Receive stock and create a posted sale for returns
        app(InventoryService::class)->receiveStock(
            $this->company->id,
            $this->product->id,
            null,
            20,
            5.00,
            'purchase_order',
            1,
            now()->toDateString()
        );

        $this->sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $this->session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 55.00],
            ],
        ], $this->user->id);
    }

    private function baseReturnData(array $overrides = []): array
    {
        $saleLine = $this->sale->lines->first();

        return array_merge([
            'company_id' => $this->company->id,
            'pos_sale_id' => $this->sale->id,
            'date' => now()->toDateString(),
            'reason' => 'Customer changed mind',
            'lines' => [
                ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 2],
            ],
        ], $overrides);
    }

    // =============================================
    // BASIC RETURN
    // =============================================

    public function test_process_return_creates_return(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $this->assertNotNull($return);
        $this->assertEquals('posted', $return->status);
        $this->assertStringStartsWith('RTN-', $return->return_number);
    }

    public function test_return_totals_are_correct(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        // 2 × $10.00 = $20.00 subtotal, tax 10% = $2.00, total = $22.00
        $this->assertEquals(20.00, $return->subtotal);
        $this->assertEquals(2.00, $return->tax_total);
        $this->assertEquals(22.00, $return->total);
    }

    public function test_return_creates_lines(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $this->assertCount(1, $return->lines);
        $line = $return->lines->first();
        $this->assertEquals(2, $line->quantity_returned);
        $this->assertEquals(10.00, $line->unit_price);
        $this->assertEquals(2.00, $line->tax_amount);
        $this->assertEquals(22.00, $line->line_total);
    }

    public function test_return_number_is_unique(): void
    {
        $r1 = app(PosReturnService::class)->processReturn($this->baseReturnData(), $this->user->id);

        // Second return with a fresh sale
        $sale2 = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $this->session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 33.00],
            ],
        ], $this->user->id);

        $saleLine2 = $sale2->lines->first();
        $r2 = app(PosReturnService::class)->processReturn([
            'company_id' => $this->company->id,
            'pos_sale_id' => $sale2->id,
            'date' => now()->toDateString(),
            'lines' => [
                ['pos_sale_line_id' => $saleLine2->id, 'quantity_returned' => 1],
            ],
        ], $this->user->id);

        $this->assertNotEquals($r1->return_number, $r2->return_number);
    }

    // =============================================
    // GL POSTING (reversal)
    // =============================================

    public function test_return_posts_reversal_journal_entry(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $this->assertNotNull($return->journal_entry_id);
        $je = $return->journalEntry;
        $this->assertEquals('posted', $je->status);
        $this->assertEquals('pos', $je->source_module);

        $totalDebit = $je->lines->sum('debit');
        $totalCredit = $je->lines->sum('credit');
        $this->assertEquals(round($totalDebit, 2), round($totalCredit, 2));
    }

    public function test_return_debits_revenue(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $revenueLine = $return->journalEntry->lines
            ->where('account_id', $this->revenueAccount->id)
            ->first();

        $this->assertNotNull($revenueLine);
        $this->assertEquals(20.00, $revenueLine->debit);
    }

    public function test_return_debits_tax_payable(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $taxLine = $return->journalEntry->lines
            ->where('account_id', $this->taxPayable->id)
            ->first();

        $this->assertNotNull($taxLine);
        $this->assertEquals(2.00, $taxLine->debit);
    }

    public function test_return_credits_clearing_account(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $cashInDrawer = Account::where('company_id', $this->company->id)->where('code', '1060')->first();
        $clearingLine = $return->journalEntry->lines
            ->where('account_id', $cashInDrawer->id)
            ->first();

        $this->assertNotNull($clearingLine);
        $this->assertEquals(22.00, $clearingLine->credit);
    }

    public function test_return_reverses_cogs_and_inventory(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $saleLine = $this->sale->lines->first();
        $expectedCogs = round(2 * ($saleLine->cost_of_goods / $saleLine->quantity), 2);

        $cogsLine = $return->journalEntry->lines
            ->where('account_id', $this->cogsAccount->id)
            ->first();
        $invLine = $return->journalEntry->lines
            ->where('account_id', $this->invAsset->id)
            ->first();

        $this->assertNotNull($cogsLine);
        $this->assertNotNull($invLine);
        $this->assertEquals($expectedCogs, $cogsLine->credit);
        $this->assertEquals($expectedCogs, $invLine->debit);
    }

    // =============================================
    // INVENTORY RESTORATION
    // =============================================

    public function test_return_restores_inventory_stock(): void
    {
        $stockBefore = InventoryStock::where('company_id', $this->company->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity_on_hand');

        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $stockAfter = InventoryStock::where('company_id', $this->company->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity_on_hand');

        $this->assertEquals($stockBefore + 2, $stockAfter);
    }

    public function test_return_creates_cost_layer(): void
    {
        $layersBefore = \App\Models\InventoryCostLayer::where('company_id', $this->company->id)
            ->where('product_id', $this->product->id)
            ->count();

        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $layersAfter = \App\Models\InventoryCostLayer::where('company_id', $this->company->id)
            ->where('product_id', $this->product->id)
            ->count();

        $this->assertEquals($layersBefore + 1, $layersAfter);

        $returnLayer = \App\Models\InventoryCostLayer::where('company_id', $this->company->id)
            ->where('product_id', $this->product->id)
            ->where('source_type', 'pos_return')
            ->first();
        $this->assertNotNull($returnLayer);
        $this->assertEquals($return->id, $returnLayer->source_id);
    }

    // =============================================
    // VALIDATION
    // =============================================

    public function test_cannot_return_from_unposted_sale(): void
    {
        $draftSale = PosSale::create([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $this->session->id,
            'sale_number' => 'DRAFT-001',
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'status' => 'draft',
        ]);

        $saleLine = PosSaleLine::create([
            'pos_sale_id' => $draftSale->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'line_total' => 10.00,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(PosReturnService::class)->processReturn([
            'company_id' => $this->company->id,
            'pos_sale_id' => $draftSale->id,
            'lines' => [
                ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 1],
            ],
        ], $this->user->id);
    }

    public function test_cannot_return_more_than_sold(): void
    {
        $saleLine = $this->sale->lines->first();

        $this->expectException(\InvalidArgumentException::class);
        app(PosReturnService::class)->processReturn(
            $this->baseReturnData([
                'lines' => [
                    ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 99],
                ],
            ]),
            $this->user->id
        );
    }

    public function test_requires_sale_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(PosReturnService::class)->processReturn([
            'company_id' => $this->company->id,
            'lines' => [['pos_sale_line_id' => 1, 'quantity_returned' => 1]],
        ], $this->user->id);
    }

    public function test_requires_return_lines(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(PosReturnService::class)->processReturn([
            'company_id' => $this->company->id,
            'pos_sale_id' => $this->sale->id,
            'lines' => [],
        ], $this->user->id);
    }

    public function test_positive_quantity_required(): void
    {
        $saleLine = $this->sale->lines->first();

        $this->expectException(\InvalidArgumentException::class);
        app(PosReturnService::class)->processReturn(
            $this->baseReturnData([
                'lines' => [
                    ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 0],
                ],
            ]),
            $this->user->id
        );
    }

    // =============================================
    // CONTROLLER
    // =============================================

    public function test_index_loads(): void
    {
        $this->get(route('pos.returns.index'))->assertOk();
    }

    public function test_create_loads(): void
    {
        $this->get(route('pos.returns.create'))->assertOk();
    }

    public function test_create_with_sale_id_loads_sale(): void
    {
        $this->get(route('pos.returns.create', ['sale_id' => $this->sale->id]))
            ->assertOk();
    }

    public function test_show_loads(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $this->get(route('pos.returns.show', $return))->assertOk();
    }

    public function test_store_creates_return(): void
    {
        $saleLine = $this->sale->lines->first();

        $this->post(route('pos.returns.store'), [
            'pos_sale_id' => $this->sale->id,
            'date' => now()->toDateString(),
            'reason' => 'Defective',
            'lines' => [
                ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('pos_returns', 1);
    }

    public function test_lines_json_loads(): void
    {
        $this->get(route('pos.sales.lines-json', $this->sale->id))
            ->assertOk()
            ->assertJsonStructure(['lines']);
    }

    // =============================================
    // COMPANY ISOLATION
    // =============================================

    public function test_company_isolation(): void
    {
        $return = app(PosReturnService::class)->processReturn(
            $this->baseReturnData(),
            $this->user->id
        );

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC2',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->assertNull(\App\Models\PosReturn::where('company_id', $otherCompany->id)->first());
    }
}
