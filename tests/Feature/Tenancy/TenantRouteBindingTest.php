<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\BindTenantConnection;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression tests for the tenant route-model-binding bug (#41): implicit binding
 * used to run BEFORE the tenant connection was bound, so `{entity}` parameters on
 * tenant routes resolved against the CENTRAL (legacy shared) database and 404'd
 * on records that exist only in the tenant database.
 */
class TenantRouteBindingTest extends TestCase
{
    use RefreshDatabase;

    private const TENANT_CONNECTION = 'tenant_test';

    protected function tearDown(): void
    {
        app(TenantConnectionResolver::class)->clear();

        if (Config::has('database.connections.' . self::TENANT_CONNECTION)) {
            $database = config('database.connections.' . self::TENANT_CONNECTION . '.database');
            DB::purge(self::TENANT_CONNECTION);
            if (is_string($database) && $database !== ':memory:' && file_exists($database)) {
                @unlink($database);
            }
        }

        parent::tearDown();
    }

    public function test_tenant_binding_runs_before_implicit_route_model_binding(): void
    {
        // Constructing the HTTP kernel syncs middleware aliases/groups onto the
        // Router (they are not applied before the first kernel handle()).
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $router = app('router');

        $tenantRoute = $router->getRoutes()->getByName('accounting.customers.show');
        $this->assertNotNull($tenantRoute, 'accounting.customers.show route should exist');

        $middleware = $router->gatherRouteMiddleware($tenantRoute);

        $bindIndex = array_search(BindTenantConnection::class, $middleware, true);
        $bindingsIndex = array_search(SubstituteBindings::class, $middleware, true);

        $this->assertNotFalse($bindIndex, 'BindTenantConnection must be gathered for tenant routes');
        $this->assertNotFalse($bindingsIndex, 'SubstituteBindings must be gathered for tenant routes');
        $this->assertLessThan(
            $bindingsIndex,
            $bindIndex,
            'BindTenantConnection must run BEFORE SubstituteBindings so route models resolve on the tenant connection'
        );

        // Guest/non-tenant routes must never be tenant-bound.
        $loginRoute = $router->getRoutes()->getByName('login');
        $this->assertNotNull($loginRoute);
        $this->assertNotContains(
            BindTenantConnection::class,
            $router->gatherRouteMiddleware($loginRoute)
        );
    }

    public function test_implicit_route_model_binding_resolves_against_the_tenant_database(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'company_code' => 'BIND',
            'name' => 'Binding Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_ACTIVE,
            'db_name' => 'acct_bind_00000001',
        ]);
        UserCompanyAssignment::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        // A SEPARATE sqlite file plays the tenant database. Its schema is the full
        // merged migration set; the customer + role grant exist ONLY there.
        $tenantFile = tempnam(sys_get_temp_dir(), 'tenant_bind_');
        Config::set('database.connections.' . self::TENANT_CONNECTION, [
            'driver' => 'sqlite',
            'database' => $tenantFile,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('tenancy.routing.connection_override', self::TENANT_CONNECTION);

        $this->artisan('migrate', ['--database' => self::TENANT_CONNECTION, '--force' => true])
            ->assertExitCode(0);

        $tenant = DB::connection(self::TENANT_CONNECTION);
        $tenant->table('companies')->insert([
            'id' => $company->id,
            'name' => $company->name,
            'company_code' => $company->company_code,
        ]);
        $tenant->table('roles')->insert([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'company_admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tenant->table('model_has_roles')->insert([
            'role_id' => 1,
            'model_type' => User::class,
            'model_id' => $user->id,
            'company_id' => $company->id,
        ]);
        $tenant->table('customers')->insert([
            'company_id' => $company->id,
            'name' => 'Tenant-Only Customer',
            'is_active' => true,
        ]);

        // Proof the customer exists ONLY in the tenant database.
        $this->assertSame(0, Customer::where('name', 'Tenant-Only Customer')->count());

        session(['current_company_id' => $company->id]);

        $response = $this->actingAs($user)
            ->get(route('accounting.customers.show', 1));

        $this->assertSame(
            200,
            $response->status(),
            'Implicit route binding must resolve the tenant-only customer via the tenant connection'
        );
        $response->assertSee('Tenant-Only Customer');
    }
}
