<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantConnectionResolverTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'company_code' => 'ACME',
            'name' => 'ACME Corp',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_ACTIVE,
            'db_name' => 'acct_acme_12345678',
            'db_host' => 'db.internal',
            'db_port' => 3307,
            'db_username' => 'tenant_user',
            'db_password' => 'secret-password',
        ], $overrides));
    }

    private function resolver(): TenantConnectionResolver
    {
        return app(TenantConnectionResolver::class);
    }

    public function test_connection_name_is_null_when_not_bound(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $this->assertNull(TenantConnectionResolver::connectionName());
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_connection_config_is_built_from_the_company_record(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $company = $this->makeCompany();

        $config = $this->resolver()->connectionConfig($company);

        $this->assertSame('acct_acme_12345678', $config['database']);
        $this->assertSame('db.internal', $config['host']);
        $this->assertSame(3307, $config['port']);
        $this->assertSame('tenant_user', $config['username']);
        $this->assertSame('secret-password', $config['password']);
    }

    public function test_resolve_binds_the_tenant_connection(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $company = $this->makeCompany();

        $this->resolver()->resolve($company);

        $this->assertTrue($this->resolver()->isBound());
        $this->assertSame($company->id, $this->resolver()->boundCompanyId());
        $this->assertSame('tenant', TenantConnectionResolver::connectionName());
        $this->assertSame('acct_acme_12345678', Config::get('database.connections.tenant.database'));
    }

    public function test_resolve_is_idempotent_for_the_same_company(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $company = $this->makeCompany();

        $this->resolver()->resolve($company);
        $this->resolver()->resolve($company);
        $this->resolver()->resolve($company);

        $this->assertTrue($this->resolver()->isBound());
        $this->assertSame($company->id, $this->resolver()->boundCompanyId());
    }

    public function test_clear_unbinds_the_tenant_connection(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $this->resolver()->resolve($this->makeCompany());

        $this->resolver()->clear();

        $this->assertFalse($this->resolver()->isBound());
        $this->assertNull(TenantConnectionResolver::connectionName());
        $this->assertNull(Config::get('database.connections.tenant'));
    }

    public function test_resolve_throws_for_unprovisioned_company(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $company = $this->makeCompany(['provisioning_status' => Company::STATUS_PENDING, 'db_name' => null]);

        $this->expectException(\RuntimeException::class);
        $this->resolver()->resolve($company);
    }

    public function test_resolve_throws_for_inactive_company(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $company = $this->makeCompany(['is_active' => false]);

        $this->expectException(\RuntimeException::class);
        $this->resolver()->resolve($company);
    }

    public function test_resolve_for_company_id_loads_and_binds(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $company = $this->makeCompany();

        $bound = $this->resolver()->resolveForCompanyId($company->id);

        $this->assertSame($company->id, $bound->id);
        $this->assertTrue($this->resolver()->isBound());
    }

    public function test_resolve_for_company_id_throws_for_missing_company(): void
    {
        Config::set('tenancy.routing.connection_override', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->resolver()->resolveForCompanyId(99999);
    }

    public function test_connection_override_resolves_models_to_the_override_connection(): void
    {
        Config::set('tenancy.routing.connection_override', 'sqlite');

        $company = $this->makeCompany();

        $this->resolver()->resolve($company);

        $this->assertTrue($this->resolver()->isBound());
        $this->assertSame('sqlite', TenantConnectionResolver::connectionName());
        $this->assertNull(Config::get('database.connections.tenant'));
    }
}
