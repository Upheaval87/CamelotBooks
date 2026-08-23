<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsConsoleRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $company = Company::create([
            'name' => 'Test Co',
            'company_code' => 'TST',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role' => 'company_admin']);

        setPermissionsTeamId($company->id);
        $user->assignRole('company_admin');

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);
    }

    public function test_permissions_index_renders(): void
    {
        $response = $this->get('/admin/permissions');
        $response->assertStatus(200);
        $response->assertSee('rpc-wrap');
        $response->assertSee('rpc-data');
    }

    public function test_role_permissions_endpoint_returns_json(): void
    {
        $role = \Spatie\Permission\Models\Role::first();
        $response = $this->getJson('/admin/permissions/' . $role->id . '/permissions');
        $response->assertStatus(200);
        $response->assertJsonStructure(['ok', 'role' => ['id', 'name', 'label', 'is_active'], 'granted', 'matrix']);
    }

    public function test_permissions_index_sees_data_script(): void
    {
        $response = $this->get('/admin/permissions');
        $response->assertSee('id="rpc-data"', false);
        $response->assertSee('moduleGroups', false);
        $response->assertSee('rpc-wrap', false);
    }
}
