<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\FeatureManagement;
use App\Services\SuperAdmin\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeCompany(string $code = 'ACME', bool $provisioned = true, bool $active = true): Company
    {
        return Company::create([
            'company_code' => $code,
            'name' => $code . ' Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => $active,
            'provisioning_status' => $provisioned ? Company::STATUS_ACTIVE : Company::STATUS_PENDING,
            'db_name' => $provisioned ? 'acct_' . strtolower($code) . '_00000001' : null,
            'provisioned_at' => $provisioned ? now() : null,
        ]);
    }

    private function assign(User $user, Company $company, string $role = 'company_admin'): UserCompanyAssignment
    {
        return UserCompanyAssignment::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => $role,
            'branch_ids' => [],
            'is_active' => true,
        ]);
    }

    // ---------- Access gate ----------

    public function test_non_super_admin_is_forbidden_from_the_panel(): void
    {
        $response = $this->actingAs($this->makeUser())->get(route('superadmin.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_the_panel(): void
    {
        $response = $this->get(route('superadmin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_reach_all_panel_sections(): void
    {
        $super = $this->makeSuperAdmin();
        $this->actingAs($super);

        foreach ([
            'superadmin.dashboard',
            'superadmin.companies.index',
            'superadmin.companies.create',
            'superadmin.users.index',
            'superadmin.users.create',
            'superadmin.assignments.index',
            'superadmin.assignments.create',
            'superadmin.audit.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    // ---------- Companies ----------

    public function test_company_list_shows_counts_and_status(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');
        $user = $this->makeUser();
        $this->assign($user, $company);
        $this->makeCompany('BETA', provisioned: false);

        $this->actingAs($super)
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee('ACME Company')
            ->assertSee('BETA Company');
    }

    public function test_company_creation_success_path_redirects_to_show(): void
    {
        $super = $this->makeSuperAdmin();

        $this->mock(\App\Services\Tenancy\CompanyProvisioningService::class, function ($mock) {
            $mock->shouldReceive('provision')->once();
        });

        $response = $this->actingAs($super)->post(route('superadmin.companies.store'), [
            'name' => 'Zulu Trading',
            'company_code' => 'ZULU',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'branch_limit' => 3,
        ]);

        $company = Company::where('company_code', 'ZULU')->firstOrFail();
        $response->assertRedirect(route('superadmin.companies.show', $company));

        $this->assertSame(3, $company->branch_limit);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_COMPANY_CREATED,
        ]);
        $this->assertDatabaseMissing('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_COMPANY_PROVISION_FAILED,
        ]);
    }

    public function test_company_creation_records_audit_and_falls_back_on_provision_failure(): void
    {
        $super = $this->makeSuperAdmin();

        $this->mock(\App\Services\Tenancy\CompanyProvisioningService::class, function ($mock) {
            $mock->shouldReceive('provision')->once()->andThrow(new \RuntimeException('simulated failure'));
        });

        $response = $this->actingAs($super)->post(route('superadmin.companies.store'), [
            'name' => 'Zulu Trading',
            'company_code' => 'ZULU',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'branch_limit' => 3,
        ]);

        $company = Company::where('company_code', 'ZULU')->firstOrFail();

        $response->assertRedirect(route('superadmin.companies.show', $company));
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_COMPANY_CREATED,
        ]);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_COMPANY_PROVISION_FAILED,
        ]);
    }

    public function test_company_can_be_suspended_and_reactivated(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');

        $this->actingAs($super)->post(route('superadmin.companies.suspend', $company));

        $this->assertFalse($company->fresh()->is_active);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_COMPANY_SUSPENDED,
        ]);

        $this->actingAs($super)->post(route('superadmin.companies.reactivate', $company));

        $this->assertTrue($company->fresh()->is_active);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_COMPANY_REACTIVATED,
        ]);
    }

    public function test_db_preview_returns_a_predictable_name(): void
    {
        $response = $this->actingAs($this->makeSuperAdmin())
            ->getJson(route('superadmin.db-preview') . '?name=Zulu%20Trading');

        $response->assertOk();
        $this->assertSame('acct_zulu_trading_xxxxxxxx', $response->json('db_name'));
    }

    // ---------- Companies: edit / update ----------

    public function test_company_edit_form_renders_prefilled_values(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');

        $this->actingAs($super)
            ->get(route('superadmin.companies.edit', $company))
            ->assertOk()
            ->assertSee('value="ACME Company"', false)
            ->assertSee('value="ACME"', false)
            ->assertSee('value="USD" selected', false)
            ->assertSee('USD - US Dollar');
    }

    public function test_company_edit_page_is_forbidden_for_non_super_admins(): void
    {
        $company = $this->makeCompany('ACME');

        $this->actingAs($this->makeUser())
            ->get(route('superadmin.companies.edit', $company))
            ->assertForbidden();
    }

    public function test_super_admin_can_update_company_details_with_audit(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');

        $response = $this->actingAs($super)->patch(route('superadmin.companies.update', $company), [
            'name' => 'ACME Holdings Ltd',
            'legal_name' => 'ACME Holdings Limited',
            'company_code' => 'ACME',
            'tax_id' => 'MW-123456',
            'address' => '1 Independence Drive',
            'city' => 'Lilongwe',
            'state' => null,
            'country' => 'Malawi',
            'postal_code' => null,
            'phone' => '+265 1 234 567',
            'email' => 'info@acme.example.com',
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 7,
        ]);

        $response->assertRedirect(route('superadmin.companies.show', $company));

        $fresh = $company->fresh();
        $this->assertSame('ACME Holdings Ltd', $fresh->name);
        $this->assertSame('MWK', $fresh->base_currency);
        $this->assertSame(7, $fresh->fiscal_year_start_month);
        $this->assertSame('Lilongwe', $fresh->city);

        $audit = SuperAdminAuditLog::query()
            ->where('company_id', $company->id)
            ->where('action', SuperAdminAuditLog::ACTION_COMPANY_UPDATED)
            ->firstOrFail();
        $this->assertSame($super->id, $audit->user_id);
        $this->assertSame('MWK', $audit->after['base_currency']);
        $this->assertSame('USD', $audit->before['base_currency']);
        $this->assertSame(7, $audit->after['fiscal_year_start_month']);
    }

    public function test_company_update_keeps_its_own_code(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');

        $this->actingAs($super)
            ->patch(route('superadmin.companies.update', $company), [
                'name' => 'ACME Company',
                'company_code' => 'ACME',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
            ])
            ->assertRedirect(route('superadmin.companies.show', $company));
    }

    public function test_company_update_rejects_a_duplicate_code(): void
    {
        $super = $this->makeSuperAdmin();
        $this->makeCompany('ACME');
        $other = $this->makeCompany('BETA');

        $this->actingAs($super)
            ->patch(route('superadmin.companies.update', $other), [
                'name' => 'BETA Company',
                'company_code' => 'ACME',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
            ])
            ->assertSessionHasErrors('company_code');
    }

    // ---------- Companies: branch limit ----------

    public function test_company_creation_requires_branch_limit(): void
    {
        $this->mock(\App\Services\Tenancy\CompanyProvisioningService::class, function ($mock) {
            $mock->shouldReceive('provision')->never();
        });

        $this->actingAs($this->makeSuperAdmin())
            ->post(route('superadmin.companies.store'), [
                'name' => 'Zulu Trading',
                'company_code' => 'ZULU',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
            ])
            ->assertSessionHasErrors('branch_limit');

        $this->assertDatabaseMissing('companies', ['company_code' => 'ZULU']);
    }

    public function test_company_creation_rejects_a_negative_branch_limit(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->post(route('superadmin.companies.store'), [
                'name' => 'Zulu Trading',
                'company_code' => 'ZULU',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'branch_limit' => -1,
            ])
            ->assertSessionHasErrors('branch_limit');

        $this->assertDatabaseMissing('companies', ['company_code' => 'ZULU']);
    }

    public function test_super_admin_can_set_and_clear_the_branch_limit_with_audit(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');

        $response = $this->actingAs($super)
            ->patch(route('superadmin.companies.branch-limit', $company), ['branch_limit' => 4]);

        $response->assertRedirect(route('superadmin.companies.show', $company));
        $this->assertSame(4, $company->fresh()->branch_limit);

        $audit = SuperAdminAuditLog::query()
            ->where('company_id', $company->id)
            ->where('action', SuperAdminAuditLog::ACTION_COMPANY_BRANCH_LIMIT_UPDATED)
            ->firstOrFail();
        $this->assertSame($super->id, $audit->user_id);
        $this->assertNull($audit->before['branch_limit']);
        $this->assertSame(4, $audit->after['branch_limit']);

        // NULL (empty) clears back to unlimited.
        $this->actingAs($super)
            ->patch(route('superadmin.companies.branch-limit', $company), ['branch_limit' => ''])
            ->assertRedirect(route('superadmin.companies.show', $company));

        $this->assertNull($company->fresh()->branch_limit);
    }

    public function test_super_admin_can_set_a_zero_branch_limit(): void
    {
        $company = $this->makeCompany('ACME');

        $this->actingAs($this->makeSuperAdmin())
            ->patch(route('superadmin.companies.branch-limit', $company), ['branch_limit' => 0])
            ->assertRedirect();

        $this->assertSame(0, $company->fresh()->branch_limit);
    }

    public function test_branch_limit_update_rejects_negative_values(): void
    {
        $company = $this->makeCompany('ACME');

        $this->actingAs($this->makeSuperAdmin())
            ->patch(route('superadmin.companies.branch-limit', $company), ['branch_limit' => -2])
            ->assertSessionHasErrors('branch_limit');
    }

    public function test_branch_limit_update_is_forbidden_for_non_super_admins(): void
    {
        $company = $this->makeCompany('ACME');

        $this->actingAs($this->makeUser())
            ->patch(route('superadmin.companies.branch-limit', $company), ['branch_limit' => 4])
            ->assertForbidden();
    }

    public function test_company_show_renders_branch_usage(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');
        $company->update(['branch_limit' => 5, 'branch_count' => 2]);

        $this->actingAs($super)
            ->get(route('superadmin.companies.show', $company))
            ->assertOk()
            ->assertSee('Branch Limit');
    }

    // ---------- Users ----------

    public function test_user_crud_and_audit(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->post(route('superadmin.users.store'), [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $response->assertRedirect(route('superadmin.users.show', $user));
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_super_admin);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_USER_CREATED,
            'target_id' => $user->id,
        ]);

        $this->actingAs($super)->patch(route('superadmin.users.update', $user), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_super_admin' => '1',
        ]);

        $user->refresh();
        $this->assertSame('Jane Doe', $user->name);
        $this->assertTrue($user->is_super_admin);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_USER_UPDATED,
            'target_id' => $user->id,
        ]);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = $this->makeUser();
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)->post(route('superadmin.users.deactivate', $user));

        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_USER_DEACTIVATED,
            'target_id' => $user->id,
        ]);

        \Illuminate\Support\Facades\Auth::logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)->post(route('superadmin.users.deactivate', $super))->assertRedirect();

        $this->assertTrue($super->fresh()->is_active);
        $this->assertDatabaseMissing('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_USER_DEACTIVATED,
        ]);
    }

    public function test_password_reset_unlocks_and_clears_failed_attempts(): void
    {
        $super = $this->makeSuperAdmin();
        $user = User::factory()->create([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addHour(),
        ]);

        $this->actingAs($super)->post(route('superadmin.users.reset-password', $user), [
            'new_password' => 'newsecret123',
            'new_password_confirmation' => 'newsecret123',
        ]);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_USER_PASSWORD_RESET,
            'target_id' => $user->id,
        ]);

        \Illuminate\Support\Facades\Auth::logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'newsecret123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    // ---------- Assignments ----------

    public function test_assignment_is_created_with_role_and_branch_scope_and_syncs_spatie_roles(): void
    {
        $super = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $company = $this->makeCompany('ACME');
        Role::findOrCreate('company_admin');
        Role::findOrCreate('viewer');

        $this->actingAs($super)->post(route('superadmin.assignments.store'), [
            'user_id' => $user->id,
            'assignments' => [
                [
                    'company_id' => $company->id,
                    'role' => 'company_admin',
                    'branch_ids' => [1, 2],
                ],
            ],
        ]);

        $assignment = UserCompanyAssignment::where('user_id', $user->id)->where('company_id', $company->id)->firstOrFail();
        $this->assertSame('company_admin', $assignment->role);
        $this->assertSame([1, 2], $assignment->branch_ids);

        $this->assertUserHasCompanyRole($user, $company->id, 'company_admin');
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_ASSIGNMENT_CREATED,
            'target_id' => $assignment->id,
        ]);
    }

    public function test_assignment_update_and_removal_sync_roles(): void
    {
        $super = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $company = $this->makeCompany('ACME');
        Role::findOrCreate('company_admin');
        Role::findOrCreate('viewer');

        $this->actingAs($super)->post(route('superadmin.assignments.store'), [
            'user_id' => $user->id,
            'assignments' => [
                ['company_id' => $company->id, 'role' => 'company_admin'],
            ],
        ]);

        $assignment = UserCompanyAssignment::where('user_id', $user->id)->where('company_id', $company->id)->firstOrFail();
        $this->assertUserHasCompanyRole($user, $company->id, 'company_admin');

        $this->actingAs($super)->patch(route('superadmin.assignments.update', $assignment), [
            'role' => 'viewer',
        ]);

        $assignment->refresh();
        $this->assertSame('viewer', $assignment->role);
        $this->assertUserHasCompanyRole($user, $company->id, 'viewer');
        $this->assertUserLacksCompanyRole($user, $company->id, 'company_admin');
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_ASSIGNMENT_UPDATED,
            'target_id' => $assignment->id,
        ]);

        $this->actingAs($super)->delete(route('superadmin.assignments.destroy', $assignment));

        $this->assertDatabaseMissing('user_company_assignments', ['id' => $assignment->id]);
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('team_id', $company->id)
            ->count());
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'action' => SuperAdminAuditLog::ACTION_ASSIGNMENT_DELETED,
            'target_id' => $assignment->id,
        ]);
    }

    private function assertUserHasCompanyRole(User $user, int $companyId, string $role): void
    {
        setPermissionsTeamId($companyId);
        $this->assertTrue($user->fresh()->hasRole($role));
    }

    private function assertUserLacksCompanyRole(User $user, int $companyId, string $role): void
    {
        setPermissionsTeamId($companyId);
        $this->assertFalse($user->fresh()->hasRole($role));
    }

    public function test_assignment_rejects_non_catalog_roles(): void
    {
        $super = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $company = $this->makeCompany('ACME');

        $this->actingAs($super)
            ->post(route('superadmin.assignments.store'), [
                'user_id' => $user->id,
                'assignments' => [
                    ['company_id' => $company->id, 'role' => 'not-a-real-role'],
                ],
            ])
            ->assertSessionHasErrors('assignments.0.role');
    }

    public function test_role_catalog_excludes_system_admin(): void
    {
        $roles = RoleCatalog::companyRoles();

        $this->assertArrayNotHasKey('system_admin', $roles);
        $this->assertArrayHasKey('company_admin', $roles);
        $this->assertArrayHasKey('viewer', $roles);
    }

    // ---------- Modules ----------

    public function test_feature_management_reads_central_company_modules(): void
    {
        $company = $this->makeCompany('ACME');
        $actor = $this->makeSuperAdmin();
        $module = Module::where('code', 'analytics')->first();

        $this->assertFalse(FeatureManagement::isEnabled($company->id, 'analytics'));

        FeatureManagement::enable($company->id, 'analytics', $actor->id);

        $this->assertTrue(FeatureManagement::isEnabled($company->id, 'analytics'));
        $this->assertDatabaseHas('company_modules', [
            'company_id' => $company->id,
            'module_id' => $module->id,
            'is_active' => 1,
            'activated_by' => $actor->id,
        ]);

        FeatureManagement::disable($company->id, 'analytics', $actor->id);

        $this->assertFalse(FeatureManagement::isEnabled($company->id, 'analytics'));
    }

    public function test_module_toggle_and_audit(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');
        $module = Module::where('code', 'analytics')->firstOrFail();

        $this->actingAs($super)->post(route('superadmin.companies.modules.toggle', [$company, $module]));

        $this->assertTrue(FeatureManagement::isEnabled($company->id, 'analytics'));
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_MODULE_ENABLED,
            'target_id' => $module->id,
        ]);

        $this->actingAs($super)->post(route('superadmin.companies.modules.toggle', [$company, $module]));

        $this->assertFalse(FeatureManagement::isEnabled($company->id, 'analytics'));
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_MODULE_DISABLED,
            'target_id' => $module->id,
        ]);
    }

    public function test_core_module_cannot_be_toggled(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');
        $core = Module::where('is_core', true)->firstOrFail();

        $this->actingAs($super)
            ->post(route('superadmin.companies.modules.toggle', [$company, $core]))
            ->assertForbidden();
    }

    public function test_modules_screen_lists_core_and_feature_modules(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');
        FeatureManagement::enable($company->id, 'analytics');

        $this->actingAs($super)
            ->get(route('superadmin.companies.modules', $company))
            ->assertOk()
            ->assertSee('Analytics');
    }

    // ---------- Audit log ----------

    public function test_audit_log_is_written_to_the_central_table_and_is_filterable(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->makeCompany('ACME');
        $module = Module::where('code', 'analytics')->firstOrFail();

        $this->actingAs($super)->post(route('superadmin.companies.modules.toggle', [$company, $module]));

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $company->id,
            'action' => SuperAdminAuditLog::ACTION_MODULE_ENABLED,
            'target_id' => $module->id,
        ]);

        $this->actingAs($super)
            ->get(route('superadmin.audit.index') . '?action=module.enabled')
            ->assertOk()
            ->assertSee('module.enabled');
    }

    public function test_edit_pages_render(): void
    {
        $super = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $company = $this->makeCompany('ACME');
        $assignment = $this->assign($user, $company);

        $this->actingAs($super)
            ->get(route('superadmin.users.edit', $user))
            ->assertOk();

        $this->actingAs($super)
            ->get(route('superadmin.assignments.edit', $assignment))
            ->assertOk();
    }

    // ---------- Deactivated user gate ----------

    public function test_deactivated_super_admin_is_blocked_from_panel(): void
    {
        $super = $this->makeSuperAdmin();
        $super->update(['is_active' => false]);

        $this->actingAs($super)->get(route('superadmin.dashboard'))->assertForbidden();
    }
}
