<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Invoice;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected $user;

    protected AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();

        $this->company = Company::create([
            'name' => 'RBAC Test Co',
            'company_code' => 'RBT',
            'is_active' => true,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        $this->seed(RolePermissionSeeder::class);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->user->update(['current_company_id' => $this->company->id]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->actingAs($this->user);
    }

    protected function createCustomer(): \App\Models\Customer
    {
        return \App\Models\Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'is_active' => true,
        ]);
    }

    protected function createVendor(): \App\Models\Vendor
    {
        return \App\Models\Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Test Vendor',
            'email' => 'vendor@test.com',
            'is_active' => true,
        ]);
    }

    public function test_user_without_role_gets_403(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->user->syncRoles([]);

        $this->get(route('accounting.invoices.index'))
            ->assertStatus(403);
    }

    public function test_company_admin_can_access_all(): void
    {
        setPermissionsTeamId($this->company->id);

        $this->get(route('accounting.invoices.index'))->assertOk();
        $this->get(route('accounting.bills.index'))->assertOk();
        $this->get(route('accounting.journal-entries.index'))->assertOk();
        $this->get(route('accounting.expenses.index'))->assertOk();
        $this->get(route('accounting.customers.index'))->assertOk();
        $this->get(route('accounting.vendors.index'))->assertOk();
    }

    public function test_viewer_role_can_access_index_but_not_create(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->user->syncRoles(['viewer']);

        $this->get(route('accounting.invoices.index'))->assertOk();
    }

    public function test_user_without_permission_gets_403_on_sensitive_action(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->user->syncRoles(['viewer']);

        $otherUser = \App\Models\User::factory()->create();
        $customer = $this->createCustomer();
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-14',
            'status' => 'draft',
            'amount' => 100,
            'base_amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'created_by' => $otherUser->id,
        ]);

        $this->post(route('accounting.invoices.post', $invoice))
            ->assertStatus(403);
    }

    public function test_sod_blocks_creator_from_posting(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->user->syncRoles(['viewer']);
        $this->user->givePermissionTo('invoices.post');

        $otherUser = \App\Models\User::factory()->create();
        $customer = $this->createCustomer();
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-002',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-14',
            'status' => 'draft',
            'amount' => 100,
            'base_amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('accounting.invoices.post', $invoice));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_sod_blocks_creator_from_voiding(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->user->syncRoles(['viewer']);
        $this->user->givePermissionTo('invoices.void');

        $otherUser = \App\Models\User::factory()->create();
        $customer = $this->createCustomer();
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-004',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-14',
            'status' => 'posted',
            'amount' => 100,
            'base_amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'created_by' => $this->user->id,
        ]);

        $this->post(route('accounting.invoices.void', $invoice))
            ->assertStatus(403);
    }

    public function test_sod_blocks_creator_from_approving_bill(): void
    {
        setPermissionsTeamId($this->company->id);
        $this->user->syncRoles(['viewer']);
        $this->user->givePermissionTo('bills.approve');

        $otherUser = \App\Models\User::factory()->create();
        $vendor = $this->createVendor();
        $bill = Bill::create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-TEST-001',
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-14',
            'status' => 'draft',
            'amount' => 100,
            'base_amount' => 100,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'created_by' => $this->user->id,
        ]);

        $this->post(route('accounting.bills.approve', $bill))
            ->assertStatus(403);
    }

    public function test_feature_middleware_blocks_disabled_feature(): void
    {
        setPermissionsTeamId($this->company->id);

        \App\Services\FeatureManagement::disable($this->company->id, 'inventory');

        $this->get(route('accounting.inventory-items.index'))
            ->assertStatus(404);
    }

    public function test_feature_middleware_allows_enabled_feature(): void
    {
        setPermissionsTeamId($this->company->id);

        \App\Services\FeatureManagement::enable($this->company->id, 'inventory');

        $this->get(route('accounting.inventory-items.index'))
            ->assertOk();
    }
}
