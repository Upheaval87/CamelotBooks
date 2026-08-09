<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesReceipt;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPageRenderTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();
        $this->company = Company::create([
            'name' => 'List Render Co',
            'company_code' => 'LST',
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->user->update(['current_company_id' => $this->company->id]);
        session(['current_company_id' => $this->company->id]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);
    }

    public function test_customers_index_renders_reference_mockup_markup(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Elvis Seyama',
            'email' => 'elvis@example.com',
            'phone' => '0999602605',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);

        $r = $this->actingAs($this->user)->get(route('accounting.customers.index'));
        $r->assertOk();
        $r->assertSee('Manage customer records, terms and balances.');
        $r->assertSee('1 customers');
        $r->assertSee('Since ' . $customer->created_at->format('M Y'));
        $r->assertSee('list-filter-count');
        $r->assertSee('aria-current="page"', false);
    }

    public function test_list_pagination_component_renders_info_and_nav(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Customer::create([
                'company_id' => $this->company->id,
                'name' => 'Customer ' . $i,
                'email' => "c{$i}@example.com",
                'is_active' => true,
            ]);
        }

        $r = $this->actingAs($this->user)->get(route('accounting.customers.index'));
        $r->assertOk();
        $r->assertSee('Showing 1–15 of 20 customers', false);
        $r->assertSee('list-pagination-nav');
        $r->assertSee('list-pagination-btn is-current');
        $r->assertSee('aria-label="Next"', false);
    }

    public function test_all_seven_list_pages_render(): void
    {
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'ACME', 'is_active' => true]);
        $vendor = Vendor::create(['company_id' => $this->company->id, 'name' => 'Supplier Co', 'is_active' => true]);
        $income = \App\Models\Account::create(['company_id' => $this->company->id, 'name' => 'Sales Income', 'type' => 'revenue', 'sub_type' => 'revenue', 'code' => '4000']);
        $expense = \App\Models\Account::create(['company_id' => $this->company->id, 'name' => 'General Expense', 'type' => 'expense', 'sub_type' => 'operating_expense', 'code' => '5000']);
        $product = Product::create(['company_id' => $this->company->id, 'name' => 'Widget', 'sku' => 'W1', 'sales_price' => 10, 'income_account_id' => $income->id, 'expense_account_id' => $expense->id, 'is_active' => true]);
        $employee = Employee::create(['company_id' => $this->company->id, 'first_name' => 'Jane', 'last_name' => 'Doe', 'employee_number' => 'E1', 'hire_date' => '2026-01-01', 'is_active' => true]);
        Invoice::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_number' => 'INV-1', 'invoice_date' => now(), 'due_date' => now()->addDays(30), 'status' => 'draft', 'amount' => 10, 'created_by' => $this->user->id]);
        Bill::create(['company_id' => $this->company->id, 'vendor_id' => $vendor->id, 'bill_number' => 'BILL-1', 'bill_date' => now(), 'due_date' => now()->addDays(30), 'status' => 'draft', 'amount' => 10, 'created_by' => $this->user->id]);
        SalesReceipt::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'receipt_number' => 'SR-1', 'receipt_date' => now(), 'status' => 'draft', 'amount' => 10, 'created_by' => $this->user->id]);

        $routes = [
            'accounting.customers.index' => 'list-table',
            'accounting.vendors.index' => 'list-table',
            'accounting.products.index' => 'list-table',
            'accounting.employees.index' => 'list-table',
            'accounting.invoices.index' => 'q2-tbl',
            'accounting.bills.index' => 'list-table',
            'accounting.sales-receipts.index' => 'li-wrap',
        ];

        foreach ($routes as $route => $marker) {
            $r = $this->actingAs($this->user)->get(route($route));
            $this->assertTrue($r->getStatusCode() === 200, "{$route} returned " . $r->getStatusCode());
            $r->assertSee($marker);
        }
    }
}
