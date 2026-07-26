<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\FeatureManagement;
use App\Services\POS\PosReturnService;
use App\Services\POS\PosSaleService;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;
    private Product $product;
    private PosPaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Audit Test Co',
            'company_code' => 'ATC',
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

        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2300'],
            ['name' => 'Tax Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1200'],
            ['name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '5000'],
            ['name' => 'COGS', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
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
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
        ]);

        $this->cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();
    }

    // =============================================
    // TILL OPEN
    // =============================================

    public function test_till_open_creates_audit_log(): void
    {
        AuditLog::query()->where('company_id', $this->company->id)->delete();

        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $log = AuditLog::where('company_id', $this->company->id)
            ->where('auditable_type', PosCashierSession::class)
            ->where('action', 'pos.till.opened')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($session->id, $log->auditable_id);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals(200.00, $log->new_values['opening_float']);
        $this->assertNotNull($log->notes);
    }

    // =============================================
    // TILL CLOSE
    // =============================================

    public function test_till_close_creates_audit_log(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        AuditLog::query()->where('company_id', $this->company->id)->delete();

        app(TillSessionService::class)->closeTill($session, 255.00);

        $log = AuditLog::where('company_id', $this->company->id)
            ->where('auditable_type', PosCashierSession::class)
            ->where('action', 'pos.till.closed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($session->id, $log->auditable_id);
        $this->assertEquals(PosCashierSession::STATUS_OPEN, $log->old_values['status']);
        $this->assertEquals(PosCashierSession::STATUS_CLOSED, $log->new_values['status']);
        $this->assertEquals(255.00, $log->new_values['actual_cash_count']);
    }

    // =============================================
    // SALE CREATED
    // =============================================

    public function test_sale_checkout_creates_audit_log(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        AuditLog::query()->where('company_id', $this->company->id)->delete();

        $sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 22.00],
            ],
        ], $this->user->id);

        $log = AuditLog::where('company_id', $this->company->id)
            ->where('auditable_type', PosSale::class)
            ->where('action', 'pos.sale.created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($sale->id, $log->auditable_id);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals($sale->sale_number, $log->new_values['sale_number']);
        $this->assertEquals(22.00, $log->new_values['total']);
        $this->assertStringContainsString('POS-', $log->notes);
    }

    // =============================================
    // RETURN CREATED
    // =============================================

    public function test_return_creates_audit_log(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 33.00],
            ],
        ], $this->user->id);

        AuditLog::query()->where('company_id', $this->company->id)->delete();

        $saleLine = $sale->lines->first();
        $return = app(PosReturnService::class)->processReturn([
            'company_id' => $this->company->id,
            'pos_sale_id' => $sale->id,
            'date' => now()->toDateString(),
            'lines' => [
                ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 1],
            ],
        ], $this->user->id);

        $log = AuditLog::where('company_id', $this->company->id)
            ->where('action', 'pos.return.created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($return->id, $log->auditable_id);
        $this->assertEquals($return->return_number, $log->new_values['return_number']);
        $this->assertStringContainsString('RTN-', $log->notes);
    }

    // =============================================
    // AUDIT LOGS ARE COMPANY-SCOPED
    // =============================================

    public function test_audit_logs_use_correct_company(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $log = AuditLog::where('auditable_type', PosCashierSession::class)
            ->where('action', 'pos.till.opened')
            ->first();

        $this->assertEquals($this->company->id, $log->company_id);
    }

    // =============================================
    // ALL POS ACTIONS APPEAR IN AUDIT LOG
    // =============================================

    public function test_all_pos_actions_are_logged(): void
    {
        // Create all POS data to generate all audit log entries
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 22.00],
            ],
        ], $this->user->id);

        $saleLine = $sale->lines->first();
        app(PosReturnService::class)->processReturn([
            'company_id' => $this->company->id,
            'pos_sale_id' => $sale->id,
            'date' => now()->toDateString(),
            'lines' => [
                ['pos_sale_line_id' => $saleLine->id, 'quantity_returned' => 1],
            ],
        ], $this->user->id);

        app(TillSessionService::class)->closeTill($session, 22.00);

        $expectedActions = [
            'pos.till.opened',
            'pos.till.closed',
            'pos.sale.created',
            'pos.return.created',
        ];

        $found = AuditLog::where('company_id', $this->company->id)
            ->whereIn('action', $expectedActions)
            ->pluck('action')
            ->unique()
            ->toArray();

        foreach ($expectedActions as $action) {
            $this->assertContains($action, $found, "Action '{$action}' was not logged.");
        }
    }
}
