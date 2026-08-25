<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\Branch;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\ItemCategory;
use App\Models\NumberingSequence;
use App\Models\PosPayment;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosMobileTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected PosTerminal $terminal;
    protected \App\Models\Account $revenueAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Mobile POS Co',
            'company_code' => 'MPC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        FeatureManagement::enable($this->company->id, 'pos');

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'cashier']);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'identifier' => 'POS-T1',
            'name' => 'Terminal 1',
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->revenueAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'revenue', 'is_active' => true]
        );

        PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'is_active' => true,
        ]);
        PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Card',
            'type' => 'card',
            'requires_reference' => true,
            'is_active' => true,
        ]);

        session(['current_company_id' => $this->company->id]);
        session(['pos_terminal_id' => $this->terminal->id]);
        session(['pos_terminal_branch_id' => null]);
    }

    /** Helper: acting as the cashier user with session set. */
    private function actingAsCashier()
    {
        $this->actingAs($this->user);
        session(['current_company_id' => $this->company->id]);
        session(['pos_terminal_id' => $this->terminal->id]);
        session(['pos_terminal_branch_id' => null]);
    }

    // ─── §6 Home ───

    public function test_home_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.home'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('pos-m-greet');
        $response->assertSee('pos-m-sum');
        $response->assertSee('pos-m-qa');
        $response->assertSee('Quick Actions');
        $response->assertSee('Recent Activity');
    }

    public function test_home_shows_zero_state(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.home'));
        $response->assertStatus(200);
        $response->assertSee('No sales yet today');
        $response->assertSee('0'); // sales today count
    }

    public function test_home_shows_recent_sales(): void
    {
        $this->actingAsCashier();

        $sale = PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-0001',
            'status' => 'posted',
            'total' => 150.00,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('pos.m.home'));
        $response->assertStatus(200);
        $response->assertSee('POS-0001');
        $response->assertSee('150');
    }

    public function test_home_shows_terminal_name(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.home'));
        $response->assertStatus(200);
        $response->assertSee('POS-T1');
    }

    // ─── §7 Sell ───

    public function test_sell_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.sell'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('pos-m-searchrow');
        $response->assertSee('pos-m-tabs');
        $response->assertSee('pos-m-pgrid');
        $response->assertSee('pos-m-sheet-ov');
        $response->assertSee('pos-m-pcard');
        $response->assertSee('posMobileSell');
    }

    public function test_sell_renders_products(): void
    {
        $this->actingAsCashier();

        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WDG-001',
            'sales_price' => 25.00,
            'tax_rate' => 16.5,
            'is_taxable' => true,
            'is_active' => true,
            'tracked_as_inventory' => true,
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $response = $this->get(route('pos.m.sell'));
        $response->assertStatus(200);
        $response->assertSee('Widget');
        $response->assertSee('WDG-001');
    }

    public function test_sell_renders_payment_methods(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.sell'));
        $response->assertStatus(200);
        $response->assertSee('Checkout');
    }

    public function test_sell_renders_category_tabs(): void
    {
        $this->actingAsCashier();

        ItemCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Beverages',
            'code' => 'BEV',
        ]);

        $response = $this->get(route('pos.m.sell'));
        $response->assertStatus(200);
        $response->assertSee('Beverages');
    }

    // ─── §8 Checkout (shares sell view) ───

    public function test_checkout_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.checkout'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('pos-m-payopt');
    }

    // ─── §9 Receipt ───

    public function test_receipt_renders(): void
    {
        $this->actingAsCashier();

        $sale = PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-0002',
            'status' => 'posted',
            'total' => 200.00,
            'created_by' => $this->user->id,
        ]);

        $pm = PosPaymentMethod::where('company_id', $this->company->id)->where('type', 'cash')->first();
        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'payment_method_id' => $pm->id,
            'amount' => 200.00,
            'cash_tendered' => 250.00,
            'change_given' => 50.00,
        ]);

        $response = $this->get(route('pos.m.receipt', $sale->id));
        $response->assertStatus(200);
        $response->assertSee('Sale Complete');
        $response->assertSee('POS-0002');
        $response->assertSee('pos-m-doc');
        $response->assertSee('pos-m-tick');
        $response->assertSee('Print');
        $response->assertSee('New Sale');
    }

    public function test_receipt_shows_payment_info(): void
    {
        $this->actingAsCashier();

        $sale = PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-0003',
            'status' => 'posted',
            'total' => 50.00,
            'created_by' => $this->user->id,
        ]);

        $pm = PosPaymentMethod::where('company_id', $this->company->id)->where('type', 'cash')->first();
        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'payment_method_id' => $pm->id,
            'amount' => 50.00,
            'cash_tendered' => 100.00,
            'change_given' => 50.00,
        ]);

        $response = $this->get(route('pos.m.receipt', $sale->id));
        $response->assertStatus(200);
        $response->assertSee('Cash');
        $response->assertSee('50.00');
        $response->assertSee('Cash tendered');
        $response->assertSee('100.00');
        $response->assertSee('Change');
    }

    public function test_receipt_404_on_wrong_company(): void
    {
        $this->actingAsCashier();

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OTH',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        $otherTerminal = PosTerminal::create([
            'company_id' => $otherCompany->id,
            'identifier' => 'T-OTH',
            'name' => 'Other Terminal',
            'is_active' => true,
        ]);
        $sale = PosSale::create([
            'company_id' => $otherCompany->id,
            'branch_id' => null,
            'terminal_id' => $otherTerminal->id,
            'cashier_name' => 'Other',
            'sale_number' => 'POS-9999',
            'status' => 'posted',
            'total' => 100.00,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('pos.m.receipt', $sale->id));
        $response->assertStatus(404);
    }

    // ─── Products JSON API ───

    public function test_products_json_returns_products(): void
    {
        $this->actingAsCashier();

        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda',
            'sku' => 'SODA-001',
            'barcode' => '123456789',
            'sales_price' => 10.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'tracked_as_inventory' => false,
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $response = $this->getJson(route('pos.m.products-json'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Soda', 'sku' => 'SODA-001']);
    }

    public function test_products_json_search(): void
    {
        $this->actingAsCashier();

        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda',
            'sku' => 'SODA-001',
            'sales_price' => 10.00,
            'is_active' => true,
            'tracked_as_inventory' => false,
            'income_account_id' => $this->revenueAccount->id,
        ]);
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Chips',
            'sku' => 'CHIP-001',
            'sales_price' => 5.00,
            'is_active' => true,
            'tracked_as_inventory' => false,
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $response = $this->getJson(route('pos.m.products-json') . '?q=Soda');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Soda']);
    }

    // ─── Bottom nav presence ───

    public function test_home_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.home'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
        $response->assertSee('pos-m-nav-b');
        $response->assertSee('Home');
        $response->assertSee('Receipts');
    }

    public function test_receipt_has_bottom_nav(): void
    {
        $this->actingAsCashier();

        $sale = PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-0004',
            'status' => 'posted',
            'total' => 75.00,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('pos.m.receipt', $sale->id));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    // ─── §10 Receipts (Phase C) ───

    public function test_receipts_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.receipts'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('Receipts');
        $response->assertSee('pos-m-chip');
        $response->assertSee('pos-m-filter-row');
        $response->assertSee('No receipts found.');
    }

    public function test_receipts_shows_sales_grouped_by_day(): void
    {
        $this->actingAsCashier();

        $sale = PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-0100',
            'status' => 'posted',
            'total' => 350.00,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('pos.m.receipts'));
        $response->assertStatus(200);
        $response->assertSee('POS-0100');
        $response->assertSee('350.00');
    }

    public function test_receipts_hides_voided_sales(): void
    {
        $this->actingAsCashier();

        PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-V001',
            'status' => 'voided',
            'total' => 100.00,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('pos.m.receipts'));
        $response->assertStatus(200);
        $response->assertDontSee('POS-V001');
    }

    public function test_receipts_filter_by_method(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.receipts', ['method' => 'cash']));
        $response->assertStatus(200);
        $response->assertSee('pos-m-chip--on', false);
    }

    public function test_receipts_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.receipts'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    // ─── §11 Register & Shift (Phase C) ───

    public function test_register_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.register'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('Register');
        $response->assertSee('pos-m-section-card');
        $response->assertSee('Shift Status');
        $response->assertSee('Cash Summary');
    }

    public function test_register_shows_terminal_info(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.register'));
        $response->assertStatus(200);
        $response->assertSee('POS-T1');
    }

    public function test_register_shows_zero_state(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.register'));
        $response->assertStatus(200);
        $response->assertSee('0'); // zero receipts
        $response->assertSee('0.00'); // zero revenue
    }

    public function test_register_shows_sales_totals(): void
    {
        $this->actingAsCashier();

        $sale = PosSale::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'terminal_id' => $this->terminal->id,
            'cashier_name' => $this->user->name,
            'sale_number' => 'POS-0200',
            'status' => 'posted',
            'total' => 500.00,
            'created_by' => $this->user->id,
        ]);

        $pm = PosPaymentMethod::where('company_id', $this->company->id)->where('type', 'cash')->first();
        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'payment_method_id' => $pm->id,
            'amount' => 500.00,
            'cash_tendered' => 500.00,
        ]);

        $response = $this->get(route('pos.m.register'));
        $response->assertStatus(200);
        $response->assertSee('500.00');
    }

    public function test_register_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.register'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    // ─── §12 Products (Phase C) ───

    public function test_products_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.products'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('Products');
        $response->assertSee('pos-m-search-field');
        $response->assertSee('pos-m-chips');
        $response->assertSee('No products found.');
    }

    public function test_products_shows_items(): void
    {
        $this->actingAsCashier();

        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Bottled Water',
            'sku' => 'BW-001',
            'sales_price' => 3.00,
            'is_active' => true,
            'tracked_as_inventory' => true,
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $response = $this->get(route('pos.m.products'));
        $response->assertStatus(200);
        $response->assertSee('Bottled Water');
        $response->assertSee('3.00');
    }

    public function test_products_search_filters(): void
    {
        $this->actingAsCashier();

        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Coca Cola',
            'sku' => 'CC-001',
            'sales_price' => 5.00,
            'is_active' => true,
            'tracked_as_inventory' => false,
            'income_account_id' => $this->revenueAccount->id,
        ]);
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Fanta',
            'sku' => 'FN-001',
            'sales_price' => 5.00,
            'is_active' => true,
            'tracked_as_inventory' => false,
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $response = $this->get(route('pos.m.products', ['q' => 'Coca']));
        $response->assertStatus(200);
        $response->assertSee('Coca Cola');
        $response->assertDontSee('Fanta');
    }

    public function test_products_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.products'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    // ─── §13 Settings (Phase C) ───

    public function test_settings_renders(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.settings'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-page');
        $response->assertSee('Settings');
        $response->assertSee('pos-m-settings-profile');
        $response->assertSee('Store');
        $response->assertSee('Devices');
        $response->assertSee('Preferences');
    }

    public function test_settings_shows_user_name(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.settings'));
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    public function test_settings_shows_company_name(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.settings'));
        $response->assertStatus(200);
        $response->assertSee('Mobile POS Co');
    }

    public function test_settings_has_sign_out_form(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.settings'));
        $response->assertStatus(200);
        $response->assertSee('Sign out');
        $response->assertSee('pos-logout-form');
    }

    public function test_settings_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.settings'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    // ═══════════════════════════════════════════════════════════
    // Phase D — Returnables Mobile (§14)
    // ═══════════════════════════════════════════════════════════

    private function seedReturnableAccounts(): void
    {
        $container = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1320'],
            ['name' => 'Returnable Containers', 'type' => 'asset', 'sub_type' => 'inventory', 'is_active' => true]
        );
        $liability = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2350'],
            ['name' => 'Bottle Credits Liability', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );

        DefaultAccountMapping::firstOrCreate(
            ['company_id' => $this->company->id, 'mapping_key' => 'returnable_containers'],
            ['account_id' => $container->id]
        );
        DefaultAccountMapping::firstOrCreate(
            ['company_id' => $this->company->id, 'mapping_key' => 'bottle_credits_liability'],
            ['account_id' => $liability->id]
        );

        NumberingSequence::firstOrCreate(
            ['company_id' => $this->company->id, 'document_type' => 'bottle_return_receipt'],
            ['prefix' => 'BRR', 'next_number' => 1, 'padding' => 4]
        );

        NumberingSequence::firstOrCreate(
            ['company_id' => $this->company->id, 'document_type' => 'journal_entry'],
            ['prefix' => 'JE', 'next_number' => 1, 'padding' => 4]
        );

        AccountingPeriod::firstOrCreate(
            ['company_id' => $this->company->id, 'start_date' => now()->startOfYear()->toDateString()],
            [
                'label' => now()->format('F Y'),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => 'open',
            ]
        );
    }

    private function seedReturnableProduct(): Product
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Crate',
            'sku' => 'CRT-001',
            'sales_price' => 5.00,
            'income_account_id' => $this->revenueAccount->id,
            'expense_account_id' => $this->revenueAccount->id,
            'is_active' => true,
        ]);

        \App\Models\ItemReturnable::create([
            'company_id' => $this->company->id,
            'item_id' => $product->id,
            'container_type' => 'crate',
            'deposit_value' => 5.00,
            'return_window_days' => 30,
            'container_stock_tracking' => false,
            'allow_cash_refund' => true,
        ]);

        return $product;
    }

    public function test_ret_intake_renders_form(): void
    {
        $this->actingAsCashier();
        $product = $this->seedReturnableProduct();

        $response = $this->get(route('pos.m.ret-intake'));
        $response->assertStatus(200);
        $response->assertSee('Bottle Intake');
        $response->assertSee($product->name);
        $response->assertSee('ret-container');
        $response->assertSee('ret-qty');
        $response->assertSee('Confirm Return');
        $response->assertSee('Store credit');
        $response->assertSee('Cash refund');
    }

    public function test_ret_intake_shows_only_returnable_products(): void
    {
        $this->actingAsCashier();
        $returnable = $this->seedReturnableProduct();
        $plainProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Normal Item',
            'sku' => 'NRM-001',
            'sales_price' => 10.00,
            'income_account_id' => $this->revenueAccount->id,
            'expense_account_id' => $this->revenueAccount->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('pos.m.ret-intake'));
        $response->assertStatus(200);
        $response->assertSee($returnable->name);
        $response->assertDontSee('Normal Item');
    }

    public function test_ret_intake_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.ret-intake'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    public function test_ret_intake_store_creates_returnable(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $this->actingAsCashier();
        $response = $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id,
            'bottle_count' => 5,
            'credit_to' => 'store_credit',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_returnables', [
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'bottle_count' => 5,
            'credit_amount' => 25.00,
            'status' => 'pending',
        ]);
    }

    public function test_ret_intake_store_validates_required_fields(): void
    {
        $this->actingAsCashier();
        $response = $this->post(route('pos.m.ret-intake.store'), []);
        $response->assertSessionHasErrors(['product_id', 'bottle_count']);
    }

    public function test_ret_intake_store_rejects_invalid_product(): void
    {
        $this->actingAsCashier();
        $response = $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => 99999,
            'bottle_count' => 1,
        ]);
        $response->assertSessionHasErrors('product_id');
    }

    public function test_ret_receipt_renders_brr(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $this->actingAsCashier();
        $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id,
            'bottle_count' => 3,
        ]);

        $returnable = \App\Models\PosReturnable::where('company_id', $this->company->id)->first();
        $response = $this->get(route('pos.m.ret-receipt', $returnable->id));
        $response->assertStatus(200);
        $response->assertSee('BRR-' . $returnable->brr_number);
        $response->assertSee('BOTTLE RETURN RECEIPT');
        $response->assertSee('3');
        $response->assertSee('15.00');
        $response->assertSee('CamelotBooks');
    }

    public function test_ret_receipt_404_for_wrong_company(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $otherCompany = Company::create([
            'name' => 'Other Co', 'company_code' => 'OTH',
            'base_currency' => 'USD', 'fiscal_year_start_month' => 1, 'is_active' => true,
        ]);

        $this->actingAsCashier();
        $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id, 'bottle_count' => 1,
        ]);

        $returnable = \App\Models\PosReturnable::where('company_id', $this->company->id)->first();

        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($otherCompany->id, ['role' => 'cashier']);
        $this->actingAs($otherUser);
        session(['current_company_id' => $otherCompany->id]);

        $response = $this->get(route('pos.m.ret-receipt', $returnable->id));
        $response->assertStatus(404);
    }

    public function test_ret_register_renders_list(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.ret-register'));
        $response->assertStatus(200);
        $response->assertSee('BRR Register');
        $response->assertSee('pos-m-empty');
        $response->assertSee('No BRR receipts found');
    }

    public function test_ret_register_shows_pending_count(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $this->actingAsCashier();
        $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id, 'bottle_count' => 2,
        ]);

        $response = $this->get(route('pos.m.ret-register'));
        $response->assertStatus(200);
        $response->assertSee('1 receipts');
        $response->assertSee('1 pending');
    }

    public function test_ret_register_filters_by_status(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $this->actingAsCashier();
        $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id, 'bottle_count' => 1,
        ]);

        $response = $this->get(route('pos.m.ret-register', ['status' => 'voided']));
        $response->assertStatus(200);
        $response->assertSee('pos-m-empty');
    }

    public function test_ret_register_search_by_brr(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $this->actingAsCashier();
        $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id, 'bottle_count' => 1,
        ]);

        $returnable = \App\Models\PosReturnable::where('company_id', $this->company->id)->first();

        $response = $this->get(route('pos.m.ret-register', ['q' => $returnable->brr_number]));
        $response->assertStatus(200);
        $response->assertSee($returnable->brr_number);
    }

    public function test_ret_register_has_bottom_nav(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.ret-register'));
        $response->assertStatus(200);
        $response->assertSee('pos-m-nav');
    }

    public function test_ret_receipt_shows_badge_and_expiry(): void
    {
        $this->seedReturnableAccounts();
        $product = $this->seedReturnableProduct();

        $this->actingAsCashier();
        $this->post(route('pos.m.ret-intake.store'), [
            'product_id' => $product->id, 'bottle_count' => 1,
        ]);

        $returnable = \App\Models\PosReturnable::where('company_id', $this->company->id)->first();
        $response = $this->get(route('pos.m.ret-receipt', $returnable->id));
        $response->assertStatus(200);
        $response->assertSee('pos-m-badge');
        $response->assertSee('Valid until');
    }

    public function test_home_returns_button_links_to_mobile_intake(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.home'));
        $response->assertStatus(200);
        $response->assertSee(route('pos.m.ret-intake'));
        $response->assertDontSee(route('pos.returnables.intake'));
    }

    public function test_ret_register_shows_status_chips(): void
    {
        $this->actingAsCashier();
        $response = $this->get(route('pos.m.ret-register'));
        $response->assertStatus(200);
        $response->assertSee('All');
        $response->assertSee('Pending');
        $response->assertSee('Redeemed');
        $response->assertSee('Voided');
    }
}
