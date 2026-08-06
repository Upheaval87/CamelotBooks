<?php

namespace Tests\Feature\BI;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The BI data mart is rebuilt per tenant: bi:refresh-data-mart loops over
 * provisioned companies, binds each tenant connection, and writes dims/facts
 * onto the mart connection (the bound tenant, or the test override).
 */
class RefreshDataMartTenantTest extends TestCase
{
    use RefreshDatabase;

    private function provisionedCompany(string $code, string $name): Company
    {
        return Company::create([
            'name' => $name,
            'company_code' => $code,
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_ACTIVE,
            'db_name' => "acct_" . strtolower($code) . "_00000001",
        ]);
    }

    private function seedSourceData(Company $company, User $user): array
    {
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main',
            'code' => 'MN',
            'is_active' => true,
        ]);

        $revenue = Account::create([
            'company_id' => $company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Widget',
            'sku' => 'WGT-001',
            'type' => 'goods',
            'tracked_as_inventory' => false,
            'sales_price' => 100,
            'purchase_price' => 40,
            'is_active' => true,
            'income_account_id' => $revenue->id,
        ]);

        $je = JournalEntry::create([
            'company_id' => $company->id,
            'journal_number' => 'JE-001',
            'created_by' => $user->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'posted',
            'reference' => 'REFRESH-TEST',
        ]);

        $ar = Account::create([
            'company_id' => $company->id,
            'code' => '1100',
            'name' => 'AR',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $ar->id, 'debit' => 100, 'credit' => 0]);
        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $revenue->id, 'debit' => 0, 'credit' => 100]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'journal_entry_id' => $je->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 100,
            'amount_paid' => 0,
            'status' => 'posted',
            'created_by' => $user->id,
            'branch_id' => $branch->id,
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'amount' => 100,
            'line_total' => 100,
            'income_account_id' => $revenue->id,
            'description' => 'Widget',
        ]);

        return compact('branch', 'revenue', 'customer', 'product', 'je', 'invoice');
    }

    public function test_command_rebuilds_mart_from_the_tenant_source(): void
    {
        $company = $this->provisionedCompany('MART', 'Mart Company');
        $user = User::factory()->create();
        $this->seedSourceData($company, $user);

        $exitCode = $this->artisan('bi:refresh-data-mart', ['--company' => $company->id])
            ->run();

        $this->assertSame(0, $exitCode);

        // Facts built from the tenant's own source rows, keyed by the company.
        $this->assertSame(1, DB::table('fact_sales')->where('company_key', $company->id)->count());
        $this->assertSame(2, DB::table('fact_general_ledger')->where('company_key', $company->id)->count());

        $sale = DB::table('fact_sales')->where('company_key', $company->id)->first();
        $this->assertSame('invoice', $sale->source_type);
        $this->assertSame((int) now()->format('Ymd'), (int) $sale->date_key);

        // Dims populated from the tenant source.
        $this->assertSame(1, DB::table('dim_company')->where('company_key', $company->id)->count());
        $this->assertGreaterThanOrEqual(1, DB::table('dim_branch')->where('company_key', $company->id)->count());
        $this->assertGreaterThanOrEqual(2, DB::table('dim_account')->where('company_key', $company->id)->count());
        $this->assertSame(1, DB::table('dim_customer')->where('company_key', $company->id)->count());

        // Calendar seeded so fiscal mapping has rows to update.
        $this->assertGreaterThan(0, DB::table('dim_date')->count());

        // Refresh log written on the mart connection.
        $log = DB::table('bi_refresh_log')->where('company_id', $company->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('completed', $log->status);
        $this->assertNotNull($log->completed_at);

        $rows = json_decode($log->rows_refreshed, true);
        $this->assertSame(1, $rows['fact_sales']);
        $this->assertSame(2, $rows['fact_general_ledger']);

        // The tenant binding must not leak past the command.
        $this->assertNull(app(TenantConnectionResolver::class)->connectionName());
    }

    public function test_facts_are_scoped_to_the_refreshed_company(): void
    {
        $companyA = $this->provisionedCompany('MATA', 'Company A');
        $companyB = $this->provisionedCompany('MATB', 'Company B');
        $user = User::factory()->create();

        $this->seedSourceData($companyA, $user);
        $this->seedSourceData($companyB, $user);

        $this->artisan('bi:refresh-data-mart', ['--company' => $companyA->id])->assertExitCode(0);

        $this->assertSame(1, DB::table('fact_sales')->where('company_key', $companyA->id)->count());
        $this->assertSame(0, DB::table('fact_sales')->where('company_key', $companyB->id)->count());
        $this->assertSame(1, DB::table('bi_refresh_log')->where('company_id', $companyA->id)->count());
        $this->assertSame(0, DB::table('bi_refresh_log')->where('company_id', $companyB->id)->count());
    }

    public function test_command_skips_unprovisioned_companies(): void
    {
        $pending = Company::create([
            'name' => 'Pending Co',
            'company_code' => 'PEND',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_PENDING,
        ]);

        $exitCode = $this->artisan('bi:refresh-data-mart', ['--company' => $pending->id])->run();

        $this->assertNotSame(0, $exitCode);
        $this->assertSame(0, DB::table('bi_refresh_log')->where('company_id', $pending->id)->count());
    }

    public function test_command_refreshes_all_provisioned_companies_without_option(): void
    {
        $companyA = $this->provisionedCompany('MALLA', 'All A');
        $companyB = $this->provisionedCompany('MALLB', 'All B');
        $user = User::factory()->create();

        $this->seedSourceData($companyA, $user);
        $this->seedSourceData($companyB, $user);

        $this->artisan('bi:refresh-data-mart')->assertExitCode(0);

        // Both companies are processed and logged. (With the sqlite routing
        // override both tenants share one database, so each company's rebuild
        // truncates the previous one's facts — that is expected per-tenant
        // behavior; isolation across tenants is covered by the scoped test.)
        $this->assertSame(2, DB::table('bi_refresh_log')->count());
        $this->assertSame(2, DB::table('bi_refresh_log')->where('status', 'completed')->count());
        $this->assertSame(1, DB::table('bi_refresh_log')->where('company_id', $companyA->id)->count());
        $this->assertSame(1, DB::table('bi_refresh_log')->where('company_id', $companyB->id)->count());
    }
}
