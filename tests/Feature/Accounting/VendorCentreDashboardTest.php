<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorCentreDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'VCTEST',
            'name' => 'Vendor Centre Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
        Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Supplies',
            'email' => 'sales@acme.test',
            'is_active' => true,
        ]);
    }

    public function test_dashboard_renders_with_key_sections(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.vendors.dashboard'))
            ->assertOk()
            ->assertSee('Vendors')
            ->assertSee('Accounts payable overview')
            ->assertSee('Payables Aging')
            ->assertSee('Top Vendors')
            ->assertSee('Upcoming Payments')
            ->assertSee('Needs Attention')
            ->assertSee('Vendor Balances')
            ->assertSee('New Vendor')
            ->assertSee('Purchase Order');
    }

    public function test_dashboard_stats_reflect_live_ap_ledger(): void
    {
        $bill = \App\Models\Bill::create([
            'company_id' => $this->company->id,
            'vendor_id' => Vendor::first()->id,
            'bill_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'amount' => 500.00,
            'amount_paid' => 0,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);
        $bill->lines()->create([
            'company_id' => $this->company->id,
            'description' => 'Rent',
            'quantity' => 1,
            'unit_price' => 500.00,
            'amount' => 500.00,
            'line_total' => 500.00,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '6100')->first()->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('accounting.vendors.dashboard'))
            ->assertOk()
            ->assertSee('500.00');
    }

    public function test_reports_renders_ap_report_cards(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.vendors.reports'))
            ->assertOk()
            ->assertSee('AP Aging Summary')
            ->assertSee('AP Aging Detail')
            ->assertSee('Purchases by Vendor')
            ->assertSee('Vendor Statement')
            ->assertSee('Unbilled Receipts');
    }

    public function test_settings_page_renders_and_updates(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.vendors.settings'))
            ->assertOk()
            ->assertSee('Default Payment Terms')
            ->assertSee('Due-Soon Window');

        $this->actingAs($this->user)
            ->post(route('accounting.vendors.settings.update'), [
                'default_payment_terms' => 'net_60',
                'default_currency' => 'USD',
                'due_soon_days' => 45,
            ])
            ->assertRedirect(route('accounting.vendors.settings'))
            ->assertSessionHas('success');

        $this->assertSame('net_60', SystemSetting::getValue('vendor_centre', 'default_payment_terms', $this->company->id));
        $this->assertSame(45, (int) SystemSetting::getValue('vendor_centre', 'due_soon_days', $this->company->id));
    }

    public function test_settings_update_rejects_invalid_terms(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.vendors.settings.update'), [
                'default_payment_terms' => 'net_999',
                'default_currency' => 'USD',
                'due_soon_days' => 45,
            ])
            ->assertSessionHasErrors('default_payment_terms');
    }

    public function test_export_csv_streams_vendor_rows(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('accounting.vendors.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Name,"Display Name",Email,Phone,"Payment Terms",Balance,Status', $csv);
        $this->assertStringContainsString('Acme Supplies', $csv);
        $this->assertStringContainsString('Active', $csv);
    }

    public function test_legacy_vendor_centre_routes_are_gone(): void
    {
        $this->actingAs($this->user)
            ->get('/accounting/vendor-centre')
            ->assertNotFound();
    }
}
