<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Services\Tenancy\CompanyProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_MIGRATIONS = __DIR__ . '/../../Fixtures/TenancyMigrations/valid';
    private const BROKEN_MIGRATIONS = __DIR__ . '/../../Fixtures/TenancyMigrations/broken';

    private array $provisionedDbs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMysqlAvailable();
    }

    protected function tearDown(): void
    {
        foreach ($this->provisionedDbs as $name) {
            try {
                DB::connection('provisioning')->statement("DROP DATABASE IF EXISTS `{$name}`");
            } catch (\Throwable) {
            }
        }

        $this->provisionedDbs = [];

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

    private function createCompany(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ], $overrides));
    }

    private function provisioningDatabaseNames(): array
    {
        $rows = DB::connection('provisioning')->select(
            "SELECT SCHEMA_NAME AS name FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'acct\\_%'"
        );

        return array_column($rows, 'name');
    }

    public function test_provision_creates_database_runs_migrations_and_seeds_defaults(): void
    {
        $company = $this->createCompany();

        $result = app(CompanyProvisioningService::class)->provision($company, self::VALID_MIGRATIONS);

        $this->provisionedDbs[] = $result->db_name;

        $this->assertSame(Company::STATUS_ACTIVE, $result->provisioning_status);
        $this->assertNotNull($result->db_name);
        $this->assertNotNull($result->provisioned_at);
        $this->assertNull($result->last_provisioning_error);

        $tenant = "tenant_{$company->id}";

        $this->assertTrue(Schema::connection($tenant)->hasTable('probe_table'));
        $this->assertTrue(Schema::connection($tenant)->hasTable('accounts'));
        $this->assertTrue(Schema::connection($tenant)->hasTable('numbering_sequences'));

        $this->assertSame(1, DB::connection($tenant)->table('companies')->count());

        $branch = DB::connection($tenant)->table('branches')
            ->where('company_id', $company->id)
            ->first();
        $this->assertNotNull($branch);
        $this->assertSame('Head Office', $branch->name);

        $this->assertGreaterThan(20, DB::connection($tenant)->table('accounts')->count());
        $this->assertGreaterThan(0, DB::connection($tenant)->table('default_account_mappings')->count());
        $this->assertGreaterThan(0, DB::connection($tenant)->table('numbering_sequences')->count());
        $this->assertSame(1, DB::connection($tenant)->table('fiscal_years')->count());
        $this->assertSame(12, DB::connection($tenant)->table('accounting_periods')->count());
        $this->assertSame(1, DB::connection($tenant)->table('approval_settings')->count());
        $this->assertGreaterThan(0, DB::connection($tenant)->table('approval_thresholds')->count());

        $this->assertTrue(DB::table('modules')->exists());
        $this->assertTrue(DB::table('company_modules')->where('company_id', $company->id)->exists());

        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->assertSame(1, DB::table('companies')->where('id', $company->id)->count());
    }

    public function test_provision_rolls_back_database_and_marks_company_failed_when_migration_fails(): void
    {
        $before = $this->provisioningDatabaseNames();
        $company = $this->createCompany();

        try {
            app(CompanyProvisioningService::class)->provision($company, self::BROKEN_MIGRATIONS);
            $this->fail('Expected provisioning to throw.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('must fail', $e->getMessage());
        }

        $company->refresh();

        $this->assertSame(Company::STATUS_FAILED, $company->provisioning_status);
        $this->assertNull($company->db_name);
        $this->assertNotNull($company->last_provisioning_error);
        $this->assertStringContainsString('must fail', $company->last_provisioning_error);

        $this->assertSame($before, $this->provisioningDatabaseNames(), 'Tenant database must be dropped on rollback');

        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_provision_rejects_already_active_company(): void
    {
        $company = $this->createCompany(['provisioning_status' => Company::STATUS_ACTIVE]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already provisioned');

        app(CompanyProvisioningService::class)->provision($company, self::VALID_MIGRATIONS);
    }

    public function test_generate_database_name_contains_slugged_company_code(): void
    {
        $company = $this->createCompany(['company_code' => 'ACME-NAV']);

        $name = app(CompanyProvisioningService::class)->generateDatabaseName($company);

        $this->assertMatchesRegularExpression('/^acct_acme_nav_[0-9a-f]{8}$/', $name);
    }
}
