<?php

namespace Tests\Feature\Tenancy;

use App\Models\Account;
use App\Models\Company;
use App\Services\Tenancy\CompanyProvisioningService;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-end proof that the TenantScoped trait actually routes Eloquent queries
 * to the provisioned MySQL tenant database once the resolver is bound, while the
 * default (central) connection stays untouched.
 */
class TenantRoutingMySqlTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_MIGRATIONS = __DIR__ . '/../../Fixtures/TenancyMigrations/valid';

    private array $provisionedDbs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMysqlAvailable();
        // Use the real runtime routing (no override) for this test.
        Config::set('tenancy.routing.connection_override', null);
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

    private function resolver(): TenantConnectionResolver
    {
        return app(TenantConnectionResolver::class);
    }

    public function test_tenant_scoped_models_route_to_the_tenant_database_when_bound(): void
    {
        $company = Company::create([
            'company_code' => 'ROUTE',
            'name' => 'Routing Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $provisioned = app(CompanyProvisioningService::class)->provision($company, self::VALID_MIGRATIONS);

        $this->provisionedDbs[] = $provisioned->db_name;
        $this->assertTrue($provisioned->isProvisioned());
        $this->assertNotNull($provisioned->db_name);

        // The seeder (seedDefaults=true) already wrote the tenant's companies row.
        $tenantConnection = app(CompanyProvisioningService::class)->registerConnection($provisioned, $provisioned->db_name);
        $this->assertSame(1, DB::connection($tenantConnection)->table('companies')->count());

        // Unbound: tenant-scoped models resolve to the default (central sqlite).
        $this->assertNull($this->resolver()->connectionName());
        $this->assertSame(0, Account::query()->count());

        // Bind the tenant connection for this company.
        $this->resolver()->resolve($provisioned);
        $this->assertTrue($this->resolver()->isBound());
        $this->assertSame('tenant', $this->resolver()->connectionName());

        // Eloquent writes + reads now hit the MySQL tenant database.
        Account::create([
            'company_id' => $provisioned->id,
            'code' => '1400',
            'name' => 'Short-term Investments',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_bank_account' => false,
            'is_active' => true,
        ]);

        $this->assertSame(1, Account::where('company_id', $provisioned->id)->where('code', '1400')->count());
        $this->assertSame('Short-term Investments', Account::where('code', '1400')->first()->name);
        $this->assertGreaterThan(42, Account::query()->count()); // default chart + our row

        // The default connection is still central: a Company (no trait) reads sqlite.
        $this->assertSame($company->id, Company::query()->find($company->id)->id);

        // The row truly lives in the tenant DB.
        $this->assertSame(1, DB::connection($tenantConnection)->table('accounts')->where('code', '1400')->count());
    }

    public function test_unbound_tenant_scoped_models_stay_on_the_default_connection(): void
    {
        $company = Company::create([
            'company_code' => 'UNBOUND',
            'name' => 'Unbound Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_ACTIVE,
            'db_name' => 'acct_unbound_00000000',
        ]);

        Account::create([
            'company_id' => $company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->assertNull($this->resolver()->connectionName());
        $this->assertSame(1, Account::where('company_id', $company->id)->count());
    }
}
