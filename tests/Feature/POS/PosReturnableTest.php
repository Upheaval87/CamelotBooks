<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DefaultAccountMapping;
use App\Models\ItemReturnable;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\PosReturnable;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\Accounting\InventoryService;
use App\Services\FeatureManagement;
use App\Services\POS\PosReturnableService;
use App\Services\POS\PosSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReturnableTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;
    private Product $product;
    private Account $containerAccount;
    private Account $liabilityAccount;
    private Account $revenueAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Returnable Test Co',
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
            'document_type' => 'bottle_return_receipt',
            'prefix' => 'BRR-',
            'padding_width' => 5,
            'reset_policy' => 'annually',
            'next_number' => 1,
            'is_active' => true,
        ]);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Front Counter',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        session(['pos_terminal_id' => $this->terminal->id]);
        session(['pos_terminal_branch_id' => null]);
        session(['pos_terminal_identifier' => 'T1']);

        $this->containerAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1320'],
            ['name' => 'Returnable Containers', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );
        $this->liabilityAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2350'],
            ['name' => 'Bottle Credits Liability', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );
        $this->revenueAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4050'],
            ['name' => 'Bottle Deposit Revenue', 'type' => 'income', 'sub_type' => 'revenue', 'is_active' => true]
        );

        // Map all required accounts
        foreach ([
            'returnable_containers' => '1320',
            'bottle_credits_liability' => '2350',
            'bottle_deposit_revenue' => '4050',
            'tax_payable' => '2300',
            'default_revenue' => '4000',
            'default_expense' => '5000',
            'inventory_asset' => '1200',
            'undeposited_funds' => '1050',
            'cash_in_drawer' => '1060',
            'cash_shortage' => '6900',
            'cash_overage' => '7400',
        ] as $key => $code) {
            $account = Account::firstOrCreate(
                ['company_id' => $this->company->id, 'code' => $code],
                ['name' => $code, 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
            );
            DefaultAccountMapping::setMapping($this->company->id, $key, $account->id);
        }

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Beer Bottle',
            'sku' => 'BTL-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 2.50,
            'purchase_price' => 1.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'income_account_id' => Account::firstOrCreate(
                ['company_id' => $this->company->id, 'code' => '4000'],
                ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'revenue', 'is_active' => true]
            )->id,
            'expense_account_id' => Account::firstOrCreate(
                ['company_id' => $this->company->id, 'code' => '5000'],
                ['name' => 'COGS', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
            )->id,
            'inventory_asset_account_id' => Account::firstOrCreate(
                ['company_id' => $this->company->id, 'code' => '1200'],
                ['name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
            )->id,
        ]);

        ItemReturnable::create([
            'item_id' => $this->product->id,
            'company_id' => $this->company->id,
            'container_type' => 'glass_bottle',
            'deposit_value' => 0.50,
            'deposit_tax_handling' => 'exclusive',
            'return_window_days' => 30,
            'linked_empty_item_id' => null,
            'linked_filled_item_id' => $this->product->id,
            'container_stock_account_id' => $this->containerAccount->id,
            'container_stock_tracking' => false,
            'allow_cash_refund' => true,
        ]);
    }

    // ── Model Tests ──────────────────────────────────────────

    public function test_pos_returnable_model_status_constants(): void
    {
        $this->assertEquals('pending', PosReturnable::STATUS_PENDING);
        $this->assertEquals('partially_redeemed', PosReturnable::STATUS_PARTIALLY_REDEEMED);
        $this->assertEquals('redeemed', PosReturnable::STATUS_REDEEMED);
        $this->assertEquals('expired', PosReturnable::STATUS_EXPIRED);
        $this->assertEquals('voided', PosReturnable::STATUS_VOIDED);
    }

    public function test_pos_returnable_remaining_credit_accessor(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00001',
            'brr_number' => '00001',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PARTIALLY_REDEEMED,
            'redeemed_qty' => 4,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(3.00, (float) $ret->remaining_credit);
    }

    public function test_pos_returnable_is_voidable_when_pending_and_zero_redeemed(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00002',
            'brr_number' => '00002',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($ret->isVoidable());
    }

    public function test_pos_returnable_not_voidable_when_redeemed(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00003',
            'brr_number' => '00003',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_REDEEMED,
            'redeemed_qty' => 10,
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($ret->isVoidable());
    }

    // ── Service: Intake ──────────────────────────────────────

    public function test_service_intake_creates_returnable_and_posts_je(): void
    {
        $service = app(PosReturnableService::class);
        $ret = $service->intake([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'bottle_count' => 10,
        ], $this->user->id);

        $this->assertNotNull($ret->id);
        $this->assertEquals(10, $ret->bottle_count);
        $this->assertEquals(5.00, (float) $ret->credit_amount);
        $this->assertEquals(0.50, (float) $ret->value_each);
        $this->assertEquals(PosReturnable::STATUS_PENDING, $ret->status);
        $this->assertNotNull($ret->journal_entry_id);
        $this->assertNotNull($ret->expiry_date);
        $this->assertEquals(now()->addDays(30)->toDateString(), $ret->expiry_date->toDateString());
        $this->assertStringStartsWith('BRR-', $ret->brr_number);
    }

    public function test_service_intake_fails_without_returnable_config(): void
    {
        $product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Regular Beer',
            'sku' => 'REG-001',
            'type' => 'goods',
            'sales_price' => 5.00,
            'is_active' => true,
            'income_account_id' => $this->containerAccount->id,
            'expense_account_id' => $this->containerAccount->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not configured as a returnable container');

        app(PosReturnableService::class)->intake([
            'company_id' => $this->company->id,
            'product_id' => $product2->id,
            'bottle_count' => 5,
        ], $this->user->id);
    }

    // ── Service: Redeem ──────────────────────────────────────

    public function test_service_redeem_returns_zero_when_no_customer(): void
    {
        $result = app(PosReturnableService::class)->availableCredit(
            $this->company->id,
            999
        );

        $this->assertEquals(0, $result['available_credit']);
        $this->assertEquals(0, $result['receipt_count']);
    }

    public function test_service_redeem_applies_credit_fifo(): void
    {
        // Create two returnable receipts
        $ret1 = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'customer_id' => 1,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00010',
            'brr_number' => '00010',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $ret2 = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'customer_id' => 1,
            'quantity' => 20,
            'bottle_count' => 20,
            'credit_amount' => 10.00,
            'value_each' => 0.50,
            'intake_number' => '00011',
            'brr_number' => '00011',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $result = app(PosReturnableService::class)->redeemOnCheckout(
            $this->company->id,
            1,
            null,
            7.00,
            $this->user->id
        );

        $this->assertEquals(7.00, $result['bottle_credit_applied']);
        $this->assertNotEmpty($result['returnable_ids']);

        // ret1 fully redeemed, ret2 partially
        $ret1->refresh();
        $this->assertEquals(PosReturnable::STATUS_REDEEMED, $ret1->status);
        $this->assertEquals(10, $ret1->redeemed_qty);

        $ret2->refresh();
        $this->assertEquals(PosReturnable::STATUS_PARTIALLY_REDEEMED, $ret2->status);
        // $7.00 total: $5.00 from ret1 (10 bottles) + $2.00 from ret2 (4 bottles)
        $this->assertEquals(4, $ret2->redeemed_qty);
    }

    public function test_service_redeem_does_not_exceed_sale_total(): void
    {
        PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'customer_id' => 2,
            'quantity' => 100,
            'bottle_count' => 100,
            'credit_amount' => 50.00,
            'value_each' => 0.50,
            'intake_number' => '00020',
            'brr_number' => '00020',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $result = app(PosReturnableService::class)->redeemOnCheckout(
            $this->company->id,
            2,
            null,
            3.00,
            $this->user->id
        );

        $this->assertEquals(3.00, $result['bottle_credit_applied']);
    }

    public function test_service_redeem_skips_expired_returnables(): void
    {
        PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'customer_id' => 3,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00030',
            'brr_number' => '00030',
            'expiry_date' => now()->subDays(5)->toDateString(),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $result = app(PosReturnableService::class)->redeemOnCheckout(
            $this->company->id,
            3,
            null,
            10.00,
            $this->user->id
        );

        $this->assertEquals(0, $result['bottle_credit_applied']);
    }

    // ── Service: Void ────────────────────────────────────────

    public function test_service_void_pending_returnable(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00040',
            'brr_number' => '00040',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $voided = app(PosReturnableService::class)->void($ret->id, $this->company->id, $this->user->id);

        $this->assertEquals(PosReturnable::STATUS_VOIDED, $voided->status);
    }

    public function test_service_void_throws_when_already_redeemed(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00041',
            'brr_number' => '00041',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_REDEEMED,
            'redeemed_qty' => 10,
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot void');

        app(PosReturnableService::class)->void($ret->id, $this->company->id, $this->user->id);
    }

    // ── Service: Sweep Expired ───────────────────────────────

    public function test_service_sweep_expired_forfeits_deposits(): void
    {
        PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00050',
            'brr_number' => '00050',
            'expiry_date' => now()->subDays(3)->toDateString(),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $expired = app(PosReturnableService::class)->sweepExpired($this->company->id);

        $this->assertCount(1, $expired);
        $this->assertEquals(PosReturnable::STATUS_EXPIRED, $expired->first()->status);
    }

    public function test_service_sweep_expired_ignores_pending_non_expired(): void
    {
        PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00051',
            'brr_number' => '00051',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $expired = app(PosReturnableService::class)->sweepExpired($this->company->id);

        $this->assertCount(0, $expired);
    }

    // ── HTTP: View renders ───────────────────────────────────

    public function test_intake_page_renders(): void
    {
        $this->get(route('pos.returnables.intake'))->assertOk();
    }

    public function test_index_page_renders(): void
    {
        $this->get(route('pos.returnables.index'))->assertOk();
    }

    public function test_show_page_renders(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00060',
            'brr_number' => '00060',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->get(route('pos.returnables.show', $ret->id))->assertOk();
    }

    public function test_print_page_renders(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00061',
            'brr_number' => '00061',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->get(route('pos.returnables.print', $ret->id))->assertOk();
    }

    // ── HTTP: Store intake ───────────────────────────────────

    public function test_store_intake_creates_returnable(): void
    {
        $this->post(route('pos.returnables.store-intake'), [
            'product_id' => $this->product->id,
            'bottle_count' => 5,
        ])->assertRedirect();

        $ret = PosReturnable::first();
        $this->assertNotNull($ret);
        $this->assertEquals(5, $ret->bottle_count);
        $this->assertEquals(2.50, (float) $ret->credit_amount);
        $this->assertEquals($this->company->id, $ret->company_id);
    }

    public function test_store_intake_fails_without_product(): void
    {
        $this->post(route('pos.returnables.store-intake'), [
            'bottle_count' => 5,
        ])->assertSessionHasErrors('product_id');
    }

    // ── HTTP: Void ───────────────────────────────────────────

    public function test_void_route_redirects(): void
    {
        $ret = PosReturnable::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'bottle_count' => 10,
            'credit_amount' => 5.00,
            'value_each' => 0.50,
            'intake_number' => '00070',
            'brr_number' => '00070',
            'expiry_date' => now()->addDays(30),
            'status' => PosReturnable::STATUS_PENDING,
            'redeemed_qty' => 0,
            'created_by' => $this->user->id,
        ]);

        $this->post(route('pos.returnables.void', $ret->id))
            ->assertRedirect();

        $ret->refresh();
        $this->assertEquals(PosReturnable::STATUS_VOIDED, $ret->status);
    }

    // ── HTTP: Credit check ───────────────────────────────────

    public function test_credit_check_returns_zero_for_unknown_customer(): void
    {
        $this->getJson(route('pos.returnables.credit-check', ['customer_id' => 999]))
            ->assertJson(['available_credit' => 0, 'receipt_count' => 0]);
    }
}
