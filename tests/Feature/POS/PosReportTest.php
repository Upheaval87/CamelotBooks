<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosSale;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\FeatureManagement;
use App\Services\POS\PosReportService;
use App\Services\POS\PosSaleService;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;
    private PosCashierSession $session;
    private Product $product;
    private PosPaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Report Test Co',
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

        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2300'],
            ['name' => 'Tax Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'tax_payable' => '2300',
            'default_revenue' => '4000',
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
            'tracked_as_inventory' => false,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 10.00,
            'is_taxable' => true,
            'is_active' => true,
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
        ]);

        $this->cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();

        // Create 2 sales on the session
        app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'cashier_session_id' => $this->session->id,
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10.00, 'tax_rate' => 10.00],
            ],
            'payments' => [
                ['payment_method_id' => $this->cashMethod->id, 'amount' => 22.00],
            ],
        ], $this->user->id);

        app(PosSaleService::class)->checkout([
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
    }

    // =============================================
    // X-REPORT
    // =============================================

    public function test_x_report_returns_data(): void
    {
        $data = app(PosReportService::class)->xReport($this->company->id, $this->session->id);

        $this->assertEquals(2, $data['sales_count']);
        $this->assertEquals(50.00, $data['sales_subtotal']);
        $this->assertEquals(5.00, $data['sales_tax']);
        $this->assertEquals(55.00, $data['sales_total']);
        $this->assertEquals(55.00, $data['cash_payments']);
        $this->assertEquals(200.00, $data['opening_float']);
        $this->assertEquals(255.00, $data['expected_cash']);
    }

    public function test_x_report_includes_payment_methods(): void
    {
        $data = app(PosReportService::class)->xReport($this->company->id, $this->session->id);

        $this->assertCount(1, $data['payments_by_method']);
        $this->assertEquals('Cash', $data['payments_by_method']->first()->method_name);
        $this->assertEquals(55.00, $data['payments_by_method']->first()->total_amount);
    }

    public function test_x_report_view_loads(): void
    {
        $this->get(route('pos.reports.x-report', ['session_id' => $this->session->id]))->assertOk();
    }

    // =============================================
    // Z-REPORT
    // =============================================

    public function test_z_report_returns_data(): void
    {
        // Close the session
        app(TillSessionService::class)->closeTill($this->session, 255.00);

        $data = app(PosReportService::class)->zReport($this->company->id, $this->session->fresh()->id);

        $this->assertEquals(2, $data['sales_count']);
        $this->assertEquals(55.00, $data['sales_total']);
        $this->assertEquals(55.00, $data['net_sales']);
        $this->assertEquals(0.00, $data['returns_total']);
        $this->assertEquals(255.00, $data['actual_cash_count']);
        $this->assertEquals(0.00, $data['variance']);
    }

    public function test_z_report_view_loads(): void
    {
        app(TillSessionService::class)->closeTill($this->session, 255.00);

        $this->get(route('pos.reports.z-report', ['session_id' => $this->session->fresh()->id]))->assertOk();
    }

    public function test_x_report_auto_selects_open_session(): void
    {
        $response = $this->get(route('pos.reports.x-report'));
        $response->assertOk();
    }

    public function test_z_report_redirects_to_latest_closed(): void
    {
        app(TillSessionService::class)->closeTill($this->session, 255.00);

        $response = $this->get(route('pos.reports.z-report'));
        $response->assertRedirect();
    }

    // =============================================
    // SALES BY TERMINAL
    // =============================================

    public function test_sales_by_terminal_returns_data(): void
    {
        $data = app(PosReportService::class)->salesByTerminal($this->company->id);

        $this->assertCount(1, $data['terminals']);
        $this->assertEquals(2, $data['grand_count']);
        $this->assertEquals(55.00, $data['grand_total_sales']);
    }

    public function test_sales_by_terminal_view_loads(): void
    {
        $this->get(route('pos.reports.sales-by-terminal'))->assertOk();
    }

    public function test_sales_by_terminal_with_date_filter(): void
    {
        $data = app(PosReportService::class)->salesByTerminal(
            $this->company->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(2, $data['grand_count']);
    }

    // =============================================
    // SALES BY CASHIER
    // =============================================

    public function test_sales_by_cashier_returns_data(): void
    {
        $data = app(PosReportService::class)->salesByCashier($this->company->id);

        $this->assertCount(1, $data['cashiers']);
        $this->assertEquals(2, $data['grand_count']);
        $this->assertEquals(55.00, $data['grand_total_sales']);
    }

    public function test_sales_by_cashier_view_loads(): void
    {
        $this->get(route('pos.reports.sales-by-cashier'))->assertOk();
    }

    public function test_sales_by_cashier_includes_session_count(): void
    {
        $data = app(PosReportService::class)->salesByCashier($this->company->id);

        $this->assertEquals(1, $data['cashiers'][0]['sessions_count']);
    }

    // =============================================
    // COMPANY ISOLATION
    // =============================================

    public function test_reports_are_company_scoped(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC3',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $data = app(PosReportService::class)->salesByTerminal($otherCompany->id);
        $this->assertEquals(0, $data['grand_count']);

        $data = app(PosReportService::class)->salesByCashier($otherCompany->id);
        $this->assertEquals(0, $data['grand_count']);
    }
}
