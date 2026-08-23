<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Services\Accounting\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected Role $role;
    protected int $companyId;

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
        $this->companyId = $company->id;

        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role' => 'company_admin']);

        setPermissionsTeamId($company->id);
        $user->assignRole('company_admin');

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->role = Role::create(['name' => 'test_module_perms', 'guard_name' => 'web']);
    }

    public function test_get_matrix_returns_standard_actions_for_every_module(): void
    {
        $service = new RoleService();
        $matrix = $service->getMatrix($this->role);

        $this->assertNotEmpty($matrix['modules']);

        foreach ($matrix['modules'] as $module) {
            $actionKeys = array_column($module['actions'], 'action');

            foreach (RoleService::STANDARD_ACTIONS as $standardAction) {
                $this->assertContains(
                    $standardAction,
                    $actionKeys,
                    "Module '{$module['key']}' is missing standard action '{$standardAction}'"
                );
            }
        }
    }

    public function test_get_matrix_includes_config_specific_actions(): void
    {
        $service = new RoleService();
        $matrix = $service->getMatrix($this->role);

        $invoicesModule = collect($matrix['modules'])->firstWhere('key', 'invoices');
        $this->assertNotNull($invoicesModule);

        $actionKeys = array_column($invoicesModule['actions'], 'action');
        $this->assertContains('void', $actionKeys, 'Invoices module should include config-specific action "void"');
        $this->assertContains('view', $actionKeys);
        $this->assertContains('create', $actionKeys);
        $this->assertContains('edit', $actionKeys);
        $this->assertContains('approve', $actionKeys);
        $this->assertContains('post', $actionKeys);
        $this->assertContains('delete', $actionKeys, 'Invoices should have standard action "delete" even though it is not in config');
    }

    public function test_full_roundtrip_via_api(): void
    {
        $permNames = [
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.void',
            'invoices.approve', 'invoices.post', 'invoices.delete',
        ];

        foreach ($permNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $response = $this->postJson('/admin/permissions/save', [
            'role_id' => $this->role->id,
            'permissions' => $permNames,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $reloadResponse = $this->getJson('/admin/permissions/' . $this->role->id . '/permissions');
        $reloadResponse->assertStatus(200);

        $data = $reloadResponse->json();
        $granted = $data['granted'];

        foreach ($permNames as $name) {
            $this->assertContains($name, $granted, "Permission '{$name}' should persist after save+reload");
        }

        $invoicesModule = collect($data['matrix']['modules'])->firstWhere('key', 'invoices');
        $invoicesActions = collect($invoicesModule['actions']);

        $voidAction = collect($invoicesActions)->firstWhere('action', 'void');
        $this->assertNotNull($voidAction, 'void action should be in the matrix');
        $this->assertTrue($voidAction['granted'], 'void should be granted after save');

        $deleteAction = collect($invoicesActions)->firstWhere('action', 'delete');
        $this->assertNotNull($deleteAction, 'delete action should be in the matrix');
        $this->assertTrue($deleteAction['granted'], 'delete should be granted after save');
    }

    public function test_standard_actions_not_in_config_appear_as_false(): void
    {
        Permission::firstOrCreate(['name' => 'invoices.view', 'guard_name' => 'web']);
        $this->role->syncPermissions(['invoices.view']);

        $service = new RoleService();
        $matrix = $service->getMatrix($this->role);

        $invoicesModule = collect($matrix['modules'])->firstWhere('key', 'invoices');
        $invoicesActions = collect($invoicesModule['actions']);

        $deleteAction = $invoicesActions->firstWhere('action', 'delete');
        $this->assertNotNull($deleteAction);
        $this->assertFalse($deleteAction['granted'], 'delete should be false when not assigned');

        $viewAction = $invoicesActions->firstWhere('action', 'view');
        $this->assertTrue($viewAction['granted'], 'view should be true when assigned');
    }

    public function test_custom_actions_from_config_reflect_db_state(): void
    {
        foreach (['quotations.void', 'quotations.send', 'quotations.convert'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->role->syncPermissions(['quotations.void', 'quotations.send', 'quotations.convert']);

        $service = new RoleService();
        $matrix = $service->getMatrix($this->role);

        $quotationsModule = collect($matrix['modules'])->firstWhere('key', 'quotations');
        $quotationsActions = collect($quotationsModule['actions']);

        $this->assertTrue($quotationsActions->firstWhere('action', 'void')['granted']);
        $this->assertTrue($quotationsActions->firstWhere('action', 'send')['granted']);
        $this->assertTrue($quotationsActions->firstWhere('action', 'convert')['granted']);
        $this->assertFalse($quotationsActions->firstWhere('action', 'view')['granted']);
    }

    public function test_save_via_api_preserves_void_and_delete_together(): void
    {
        foreach (['invoices.view', 'invoices.void', 'invoices.delete'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $permNames = ['invoices.view', 'invoices.void', 'invoices.delete'];
        $response = $this->postJson('/admin/permissions/save', [
            'role_id' => $this->role->id,
            'permissions' => $permNames,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $reloadResponse = $this->getJson('/admin/permissions/' . $this->role->id . '/permissions');
        $data = $reloadResponse->json();
        $granted = $data['granted'];

        $this->assertContains('invoices.void', $granted, 'void should survive a round-trip save');
        $this->assertContains('invoices.delete', $granted, 'delete should survive a round-trip save');
        $this->assertContains('invoices.view', $granted, 'view should survive a round-trip save');
    }

    public function test_matrix_has_correct_grant_counts_after_save(): void
    {
        foreach (['invoices.view', 'invoices.create', 'invoices.void'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $permNames = ['invoices.view', 'invoices.create', 'invoices.void'];
        $this->postJson('/admin/permissions/save', [
            'role_id' => $this->role->id,
            'permissions' => $permNames,
        ])->assertJson(['ok' => true]);

        $service = new RoleService();
        $this->role->refresh();
        $matrix = $service->getMatrix($this->role);

        $invoicesModule = collect($matrix['modules'])->firstWhere('key', 'invoices');
        $invoicesActions = collect($invoicesModule['actions']);
        $grantedCount = $invoicesActions->where('granted', true)->count();

        $this->assertEquals(3, $grantedCount, 'Exactly 3 invoice permissions should be granted');
        $this->assertTrue($invoicesActions->firstWhere('action', 'view')['granted']);
        $this->assertTrue($invoicesActions->firstWhere('action', 'create')['granted']);
        $this->assertTrue($invoicesActions->firstWhere('action', 'void')['granted']);
        $this->assertFalse($invoicesActions->firstWhere('action', 'delete')['granted']);
    }
}
