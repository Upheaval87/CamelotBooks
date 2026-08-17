<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\ItemCategory;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollRun;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FeatureManagement;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        foreach (['banking', 'fixed_assets', 'inventory', 'payroll', 'pos', 'purchasing', 'analytics', 'bi', 'budgets'] as $feature) {
            FeatureManagement::enable($this->company->id, $feature);
        }

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    public function test_all_registry_report_routes_render(): void
    {
        $bank = Account::create([
            'company_id' => $this->company->id,
            'code' => 'BK01',
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank' => true,
            'is_bank_account' => true,
        ]);
        $branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Main Branch', 'code' => 'BR01']);
        $costCenter = CostCenter::create(['company_id' => $this->company->id, 'name' => 'Admin', 'code' => 'CC01']);
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);
        $vendor = Vendor::create(['company_id' => $this->company->id, 'name' => 'ACME Corp']);
        $employee = Employee::create(['company_id' => $this->company->id, 'employee_number' => 'EMP001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'hire_date' => '2024-01-01']);
        $depExp = Account::create([
            'company_id' => $this->company->id,
            'code' => '6200',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'depreciation',
            'is_active' => true,
        ]);
        $category = AssetCategory::create([
            'company_id' => $this->company->id,
            'code' => 'MACH-01',
            'name' => 'Machinery',
            'depreciation_method_financial' => 'straight_line',
            'useful_life_financial' => 60,
            'residual_value_type_financial' => 'amount',
            'residual_value_financial' => 1000,
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_type_tax' => 'amount',
            'residual_value_tax' => 1000,
            'is_active' => true,
            'asset_account_id' => $this->incomeAccount->id,
            'accumulated_depreciation_account_id' => $bank->id,
            'depreciation_expense_account_id' => $depExp->id,
        ]);
        Asset::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'asset_code' => 'A-1001',
            'name' => 'Laptop',
            'acquisition_date' => '2024-01-01',
            'in_service_date' => '2024-01-01',
            'acquisition_cost' => 1000,
            'useful_life' => 36,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 36,
            'asset_account_id' => $this->incomeAccount->id,
            'accumulated_depreciation_account_id' => $bank->id,
            'depreciation_expense_account_id' => $depExp->id,
            'is_active' => true,
            'branch_id' => $branch->id,
        ]);
        PayrollRun::create([
            'company_id' => $this->company->id,
            'run_number' => 'PR-0001',
            'period_label' => 'January 2024',
            'pay_date' => '2024-01-31',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'status' => 'draft',
        ]);
        $itemCategory = ItemCategory::create([
            'company_id' => $this->company->id,
            'code' => 'CAT-01',
            'name' => 'Resale Goods',
            'default_income_account_id' => $this->incomeAccount->id,
            'default_cogs_account_id' => $depExp->id,
            'default_inventory_asset_account_id' => $bank->id,
            'default_base_uom' => 'each',
            'is_active' => true,
        ]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $itemCategory->id,
            'name' => 'Widget 3000',
            'sku' => 'WG-3000',
            'type' => 'product',
            'tracked_as_inventory' => false,
            'sales_price' => 100,
            'unit_of_measure' => 'each',
            'income_account_id' => $this->incomeAccount->id,
            'expense_account_id' => $depExp->id,
            'tax_rate' => 16.5,
            'is_taxable' => true,
            'is_active' => true,
        ]);
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-0001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => Invoice::STATUS_DRAFT,
            'amount' => 116.50,
            'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'transaction_uom' => 'each',
            'transaction_qty' => 1,
            'conversion_factor' => 1,
            'description' => 'Widget 3000',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 16.5,
            'amount' => 100,
            'tax_amount' => 16.50,
            'line_total' => 116.50,
            'income_account_id' => $this->incomeAccount->id,
            'cost_center_id' => $costCenter->id,
        ]);
        Quotation::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'quotation_number' => 'Q-0001',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(14),
            'status' => Quotation::STATUS_SENT,
            'amount' => 116.50,
            'tax_total' => 16.50,
            'total' => 116.50,
            'created_by' => $this->user->id,
        ]);
        $je = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-0001',
            'date' => now(),
            'reference' => 'REF-001',
            'memo' => 'Test journal entry',
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->user->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $bank->id,
            'branch_id' => $branch->id,
            'debit' => 100,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $this->incomeAccount->id,
            'branch_id' => $branch->id,
            'debit' => 0,
            'credit' => 100,
        ]);
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-2026-0001',
            'date' => now(),
            'expected_delivery_date' => now()->addDays(14),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'memo' => 'Test purchase order',
            'created_by' => $this->user->id,
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'description' => 'Widget 3000',
            'quantity' => 2,
            'unit_price' => 50,
            'amount' => 100,
            'expense_account_id' => $depExp->id,
            'cost_center_id' => $costCenter->id,
        ]);

        $routes = [];
        foreach (ReportRegistry::getCategories() as $catKey => $catLabel) {
            foreach (ReportRegistry::getByCategory($catKey) as $key => $report) {
                $routes[$key] = route($report['route']);
            }
        }

        $failures = [];
        foreach ($routes as $key => $url) {
            $response = $this->actingAs($this->user)->get($url);
            if ($response->status() !== 200) {
                $ex = $response->exception
                    ? get_class($response->exception) . ': ' . $response->exception->getMessage()
                    : 'no-exception';
                $loc = $response->headers->get('location') ?? $ex;
                $failures[] = "{$key} -> {$url} returned {$response->status()} ({$loc})";
            }
        }

        $this->assertEmpty($failures, implode("\n", $failures));
        $this->assertCount(74, $routes);
    }
}
