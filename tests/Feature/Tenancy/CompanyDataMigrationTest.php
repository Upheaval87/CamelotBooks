<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Services\Tenancy\CompanyDataMigrator;
use App\Services\Tenancy\CompanyProvisioningService;
use App\Services\Verification\CompanyMigrationVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const TENANT_MIGRATIONS = __DIR__ . '/../../../database/migrations/tenant';

    private array $createdDbs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMysqlAvailable();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdDbs as $name) {
            try {
                DB::connection('provisioning')->statement("DROP DATABASE IF EXISTS `{$name}`");
            } catch (\Throwable) {
            }
        }

        $this->createdDbs = [];

        parent::tearDown();
    }

    private function ensureMysqlAvailable(): void
    {
        try {
            DB::connection('provisioning')->selectOne('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL provisioning connection unavailable: ' . $e->getMessage());
        }
    }

    private function createCompany(): Company
    {
        return Company::create([
            'company_code' => 'MIGCO',
            'name' => 'Migration Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
    }

    /**
     * Create an empty MySQL database and run the real tenant migrations on it.
     * Returns the runtime connection name.
     */
    private function createSourceDatabase(): string
    {
        $name = 'migrator_source_' . substr(bin2hex(random_bytes(4)), 0, 8);
        DB::connection('provisioning')->statement("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->createdDbs[] = $name;

        $connection = 'migrator_source';
        Config::set("database.connections.{$connection}", array_merge(
            config('database.connections.provisioning'),
            ['database' => $name],
        ));
        DB::purge($connection);

        $central = DB::getDefaultConnection();

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => self::TENANT_MIGRATIONS,
                '--realpath' => true,
                '--force' => true,
            ]);
        } finally {
            DB::setDefaultConnection($central);
        }

        if ($exitCode !== 0) {
            throw new \RuntimeException('Scratch source migration failed: ' . Artisan::output());
        }

        return $connection;
    }

    private function seedSource(string $connection): void
    {
        $db = DB::connection($connection);
        $now = now();

        $db->statement('SET FOREIGN_KEY_CHECKS = 0');

        $db->table('companies')->insert(['id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $db->table('users')->insert([
            ['id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('branches')->insert([
            'id' => 1, 'company_id' => 1, 'name' => 'Head Office', 'code' => 'HQ', 'address' => null,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('accounts')->insert([
            ['id' => 1, 'company_id' => 1, 'parent_id' => null, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'company_id' => 1, 'parent_id' => null, 'code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'company_id' => 1, 'parent_id' => null, 'code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'sub_type' => 'equity', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'company_id' => 1, 'parent_id' => null, 'code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_income', 'opening_balance' => 0, 'currency' => 'USD', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('accounting_periods')->insert([
            'id' => 1, 'company_id' => 1, 'label' => 'January 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31',
            'status' => 'open', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('customers')->insert([
            'id' => 1, 'company_id' => 1, 'branch_id' => null, 'name' => 'Test Customer', 'currency' => 'USD',
            'payment_terms' => 'net_30', 'opening_balance' => 0, 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('products')->insert([
            'id' => 1, 'company_id' => 1, 'name' => 'Consulting', 'sku' => 'SV-001', 'type' => 'service',
            'sales_price' => 100.00, 'purchase_price' => null, 'income_account_id' => 4, 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('journal_entries')->insert([
            'id' => 1, 'company_id' => 1, 'branch_id' => null, 'journal_number' => 'JE-001', 'date' => '2026-01-15',
            'status' => 'posted', 'source_module' => 'manual', 'created_by' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('journal_entry_lines')->insert([
            ['id' => 1, 'journal_entry_id' => 1, 'account_id' => 1, 'branch_id' => null, 'cost_center_id' => null, 'debit' => 100.00, 'credit' => 0.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'journal_entry_id' => 1, 'account_id' => 3, 'branch_id' => null, 'cost_center_id' => null, 'debit' => 0.00, 'credit' => 100.00, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('invoices')->insert([
            'id' => 1, 'company_id' => 1, 'branch_id' => null, 'customer_id' => 1, 'invoice_number' => 'INV-001',
            'invoice_date' => '2026-01-15', 'due_date' => '2026-02-14', 'status' => 'draft',
            'amount' => 100.00, 'amount_paid' => 0.00, 'currency' => 'USD', 'created_by' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('invoice_lines')->insert([
            'id' => 1, 'invoice_id' => 1, 'product_id' => 1, 'description' => 'Consulting', 'quantity' => 1.00,
            'unit_price' => 100.00, 'discount' => 0, 'tax_rate' => 0, 'amount' => 100.00, 'tax_amount' => 0,
            'line_total' => 100.00, 'income_account_id' => 4, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('permissions')->insert([
            ['id' => 1, 'name' => 'view accounts', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'create accounts', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('roles')->insert([
            ['id' => 1, 'name' => 'company_admin', 'guard_name' => 'web', 'company_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('role_has_permissions')->insert([
            ['permission_id' => 1, 'role_id' => 1],
            ['permission_id' => 2, 'role_id' => 1],
        ]);

        $db->table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1, 'company_id' => 1],
        ]);

        // The tenant migration set has no company_user table (it is central), so
        // create a minimal copy here to exercise the membership + stub-user paths.
        Schema::connection($connection)->create('company_user', function ($table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('user_id');
            $table->string('role')->nullable();
            $table->timestamps();
        });

        $db->table('company_user')->insert([
            ['id' => 1, 'company_id' => 1, 'user_id' => 1, 'role' => 'company_admin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'company_id' => 1, 'user_id' => 2, 'role' => 'accountant', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $db->table('user_favourites')->insert([
            'id' => 1, 'user_id' => 1, 'page_key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home',
            'url' => '/dashboard', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->table('user_preferences')->insert([
            'user_id' => 1, 'sidebar_pinned' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $db->statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function test_migrates_company_data_into_tenant_and_verifies(): void
    {
        $company = $this->createCompany();

        $sourceConnection = $this->createSourceDatabase();
        $this->seedSource($sourceConnection);

        $result = app(CompanyProvisioningService::class)->provision($company, self::TENANT_MIGRATIONS, false);

        $this->createdDbs[] = $result->db_name;
        $tenantConnection = "tenant_{$company->id}";

        $this->assertSame(Company::STATUS_ACTIVE, $result->provisioning_status);

        $copied = app(CompanyDataMigrator::class)->migrate($company, $tenantConnection, $sourceConnection);

        $this->assertSame(1, $copied['branches']);
        $this->assertSame(4, $copied['accounts']);
        $this->assertSame(1, $copied['accounting_periods']);
        $this->assertSame(1, $copied['customers']);
        $this->assertSame(1, $copied['products']);
        $this->assertSame(1, $copied['journal_entries']);
        $this->assertSame(2, $copied['journal_entry_lines']);
        $this->assertSame(1, $copied['invoices']);
        $this->assertSame(1, $copied['invoice_lines']);
        $this->assertSame(2, $copied['permissions']);
        $this->assertSame(1, $copied['roles']);
        $this->assertSame(2, $copied['role_has_permissions']);
        $this->assertSame(1, $copied['model_has_roles']);
        $this->assertSame(1, $copied['user_favourites']);
        $this->assertSame(1, $copied['user_preferences']);

        // IDs preserved so intra-company FKs stay intact
        $this->assertSame(1, DB::connection($tenantConnection)->table('accounts')->where('id', 1)->count());
        $this->assertSame(1, DB::connection($tenantConnection)->table('journal_entry_lines')->where('id', 2)->where('journal_entry_id', 1)->count());

        // Stub rows: companies + users referenced anywhere
        $this->assertSame(1, DB::connection($tenantConnection)->table('companies')->count());
        $tenantUserIds = DB::connection($tenantConnection)->table('users')->pluck('id')->sort()->values()->all();
        $this->assertSame([1, 2], $tenantUserIds);

        $verification = app(CompanyMigrationVerifier::class)->verify($company, $tenantConnection, $sourceConnection);

        $this->assertTrue($verification['passed'], implode(PHP_EOL, array_map(
            static fn (array $c): string => "{$c['name']} [{$c['status']}] expected={$c['expected']} actual={$c['actual']}",
            array_filter($verification['checks'], static fn (array $c): bool => $c['status'] !== 'passed'),
        )));

        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_schema_parity_check_reports_drift(): void
    {
        $company = $this->createCompany();

        $sourceConnection = $this->createSourceDatabase();
        $this->seedSource($sourceConnection);

        $result = app(CompanyProvisioningService::class)->provision($company, self::TENANT_MIGRATIONS, false);
        $this->createdDbs[] = $result->db_name;

        $tenantConnection = "tenant_{$company->id}";

        // Introduce drift: drop a column the manifest expects to copy.
        Schema::connection($tenantConnection)->table('accounts', function ($table) {
            $table->dropColumn('sub_type');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Schema drift');

        app(CompanyDataMigrator::class)->migrate($company, $tenantConnection, $sourceConnection);
    }
}
