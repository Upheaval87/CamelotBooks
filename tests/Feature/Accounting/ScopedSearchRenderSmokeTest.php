<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedSearchRenderSmokeTest extends TestCase
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

        foreach (['banking', 'fixed_assets', 'inventory', 'payroll', 'pos', 'purchasing'] as $feature) {
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

    public function test_converted_pages_render(): void
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
        $pettyCash = Account::create([
            'company_id' => $this->company->id,
            'code' => 'PC01',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_petty_cash' => true,
        ]);
        $vendor = Vendor::create(['company_id' => $this->company->id, 'name' => 'ACME Corp']);
        $branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Main Branch', 'code' => 'BR01']);
        $costCenter = CostCenter::create(['company_id' => $this->company->id, 'name' => 'Admin', 'code' => 'CC01']);
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);
        $employee = Employee::create(['company_id' => $this->company->id, 'employee_number' => 'EMP001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'hire_date' => '2024-01-01']);
        $depExp = Account::create([
            'company_id' => $this->company->id,
            'code' => '6200',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'depreciation',
            'is_active' => true,
        ]);
        $category = \App\Models\AssetCategory::create([
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
        $asset = Asset::create([
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
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'run_number' => 'PR-0001',
            'period_label' => 'January 2024',
            'pay_date' => '2024-01-31',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'status' => 'draft',
        ]);

        $itemCategory = \App\Models\ItemCategory::create([
            'company_id' => $this->company->id,
            'code' => 'CAT-01',
            'name' => 'Resale Goods',
            'default_income_account_id' => $this->incomeAccount->id,
            'default_cogs_account_id' => $depExp->id,
            'default_inventory_asset_account_id' => $bank->id,
            'default_base_uom' => 'each',
            'is_active' => true,
        ]);
        $product = \App\Models\Product::create([
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
        $invoice = \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'cost_center_id' => $costCenter->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-0001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => \App\Models\Invoice::STATUS_DRAFT,
            'amount' => 116.50,
            'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        \App\Models\InvoiceLine::create([
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
        $quot = \App\Models\Quotation::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'quotation_number' => 'Q-0001',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(14),
            'status' => \App\Models\Quotation::STATUS_SENT,
            'amount' => 116.50,
            'tax_total' => 16.50,
            'total' => 116.50,
            'created_by' => $this->user->id,
        ]);

        $routes = [
            'bills.create' => route('accounting.bills.create'),
            'expenses.create' => route('accounting.expenses.create'),
            'expenses.index' => route('accounting.expenses.index'),
            'general-ledger.index' => route('accounting.general-ledger.index'),
            'cheques.create' => route('accounting.cheques.create'),
            'cheques.index' => route('accounting.cheques.index'),
            'cheques.register' => route('accounting.cheques.register'),
            'settlements.create' => route('pos.settlements.create'),
            'deposits.create' => route('accounting.deposits.create'),
            'customer-payments.create' => route('accounting.customer-payments.create'),
            'vendor-payments.create' => route('accounting.vendor-payments.create'),
            'fixed-assets.create' => route('accounting.fixed-assets.create'),
            'goods-received-notes.create' => route('accounting.goods-received-notes.create'),
            'landed-costs.create' => route('accounting.landed-costs.create'),
            'vendor-credits.create' => route('accounting.vendor-credits.create'),
            'purchase-orders.create' => route('accounting.purchase-orders.create'),
            'asset-usage.index' => route('accounting.asset-usage.index'),
            'asset-transfers.create' => route('accounting.asset-transfers.create'),
            'asset-revaluations.create' => route('accounting.asset-revaluations.create'),
            'asset-impairments.create' => route('accounting.asset-impairments.create'),
            'asset-disposals.create' => route('accounting.asset-disposals.create'),
            'customer-statement' => route('accounting.reports.customer-statement'),
            'vendor-statement' => route('accounting.reports.vendor-statement'),
            'payslip-report' => route('accounting.reports.payslip-report'),
            'stock-movement' => route('accounting.reports.stock-movement'),
            'accounts.index' => route('accounting.accounts.index'),
            'credit-notes.index' => route('accounting.credit-notes.index'),
            'quotations.index' => route('accounting.quotations.index'),
            'vendor-credits.index' => route('accounting.vendor-credits.index'),
            'bank-reconciliation.index' => route('accounting.bank-reconciliation.index', $bank->id),
            'audit-log.index' => route('admin.audit-log.index'),
            'report-center.index' => route('accounting.report-center.index'),
            'payroll-runs.show' => route('accounting.payroll-runs.show', $run),
            'petty-cash.show' => route('accounting.petty-cash.show', $pettyCash->id),
            'customers.index' => route('accounting.customers.index'),
            'customers.create' => route('accounting.customers.create'),
            'customers.edit' => route('accounting.customers.edit', $customer),
            'customers.show' => route('accounting.customers.show', $customer),
            'invoices.create' => route('accounting.invoices.create'),
            'invoices.create-copy-quote' => route('accounting.invoices.create', ['copy_quote' => $quot->id]),
            'invoices.create-preselect-customer' => route('accounting.invoices.create', ['customer_id' => $customer->id]),
            'invoices.edit' => route('accounting.invoices.edit', $invoice),
            'invoices.show' => route('accounting.invoices.show', $invoice),
            'invoices.copy-quote' => route('accounting.invoices.copy-quote', ['quotation' => $quot->id]),
        ];

        foreach ($routes as $name => $url) {
            $response = $this->actingAs($this->user)->get($url);
            if ($response->status() !== 200) {
                $ex = $response->exception ? get_class($response->exception) . ': ' . $response->exception->getMessage() : 'no-exception';
                $this->fail("{$name} returned {$response->status()} for {$url} -> " . ($response->headers->get('location') ?? $ex));
            }
        }

        $this->assertCount(count($routes), $routes);
    }
}
