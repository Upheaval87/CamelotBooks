<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchAuditLog;
use App\Models\Company;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\Accounting\BranchLimitExceededException;
use App\Services\Accounting\BranchLimitService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Branch-limit enforcement (multi-tenant billing prep):
 *  - per-company branch_limit/branch_count on the CENTRAL companies row;
 *  - creation serialized with a central row lock, live count reconciled, and a
 *    tenant branch_audit_log written per creation;
 *  - override honoured ONLY for verified central super admins;
 *  - NULL = unlimited, 0 = blocked;
 *  - branch writes are role-gated and cross-company-forged ids are rejected.
 */
class BranchLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(string $code = 'ACME', bool $provisioned = false): Company
    {
        return Company::create([
            'company_code' => $code,
            'name' => $code . ' Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
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

    /**
     * Authenticate a user as the session company with the company_admin role so
     * the tenant.bind/company.context/company.active/role_or_permission
     * middleware chain passes.
     */
    private function actingCompany(User $user, Company $company)
    {
        if (\Spatie\Permission\Models\Role::query()->doesntExist()) {
            $this->seed(RolePermissionSeeder::class);
        }

        setPermissionsTeamId($company->id);
        $user->assignRole('company_admin');

        return $this->actingAs($user)->withSession(['current_company_id' => $company->id]);
    }

    private function branchPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lilongwe Branch',
            'code' => 'LLW',
            'address' => '1 Independence Drive',
        ], $overrides);
    }

    public function test_company_manager_can_create_a_branch_under_the_limit(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 3]);
        $this->assign($user, $company);

        $response = $this->actingCompany($user, $company)
            ->post(route('branches.store'), $this->branchPayload());

        $response->assertRedirect(route('branches.index'));

        $branch = Branch::where('company_id', $company->id)->where('code', 'LLW')->firstOrFail();
        $this->assertTrue($branch->is_active);
        $this->assertSame(1, $company->fresh()->branch_count);
        $this->assertSame(1, Branch::where('company_id', $company->id)->count());
    }

    public function test_creation_at_the_limit_is_blocked_with_the_error_contract(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 1]);
        $this->assign($user, $company);

        Branch::create(['company_id' => $company->id, 'name' => 'Existing', 'code' => 'EXI', 'is_active' => true]);

        $response = $this->actingCompany($user, $company)
            ->postJson(route('branches.store'), $this->branchPayload(['code' => 'BTQ']));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => BranchLimitExceededException::ERROR_CODE,
                'branch_limit' => 1,
                'branch_count' => 1,
            ]);

        $this->assertSame(1, Branch::where('company_id', $company->id)->count());

        // The reconcile save happened inside the rolled-back transaction, so the
        // persisted cached count is untouched; the authoritative usage read heals it.
        $this->assertSame(0, (int) $company->fresh()->branch_count);
        $this->assertSame(1, app(BranchLimitService::class)->usage($company)['branch_count']);
    }

    public function test_creation_at_the_limit_redirects_with_error_for_non_json(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 1]);
        $this->assign($user, $company);

        Branch::create(['company_id' => $company->id, 'name' => 'Existing', 'code' => 'EXI', 'is_active' => true]);

        $response = $this->actingCompany($user, $company)
            ->post(route('branches.store'), $this->branchPayload(['code' => 'BTQ']));

        $response->assertRedirect()
            ->assertSessionHasErrors('branch_limit');

        $this->assertSame(1, Branch::where('company_id', $company->id)->count());
    }

    public function test_super_admin_without_override_is_still_blocked_at_the_limit(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 0, 'branch_count' => 0]);
        $this->assign($super, $company);

        $response = $this->actingCompany($super, $company)
            ->post(route('branches.store'), $this->branchPayload());

        $response->assertRedirect()
            ->assertSessionHasErrors('branch_limit');

        $this->assertSame(0, Branch::where('company_id', $company->id)->count());
    }

    public function test_super_admin_override_creates_even_at_the_limit_with_audit(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 1]);
        $this->assign($super, $company);

        Branch::create(['company_id' => $company->id, 'name' => 'Existing', 'code' => 'EXI', 'is_active' => true]);

        $response = $this->actingCompany($super, $company)
            ->post(route('branches.store'), $this->branchPayload(['code' => 'Ovr']) + ['override' => '1']);

        $response->assertRedirect(route('branches.index'));

        $branch = Branch::where('company_id', $company->id)->where('code', 'Ovr')->firstOrFail();
        $this->assertSame(2, $company->fresh()->branch_count);

        $log = BranchAuditLog::where('company_id', $company->id)->firstOrFail();
        $this->assertSame($branch->id, $log->branch_id);
        $this->assertSame($super->id, $log->created_by_user_id);
        $this->assertSame(BranchAuditLog::ROLE_SUPER_ADMIN, $log->created_by_role);
        $this->assertTrue($log->was_override);
        $this->assertSame(1, $log->branch_limit_at_creation);
        $this->assertSame(1, $log->branch_count_at_creation);
    }

    public function test_null_limit_never_blocks(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany(); // branch_limit stays NULL (unlimited)
        $this->assign($user, $company);

        for ($i = 1; $i <= 3; $i++) {
            $this->actingCompany($user, $company)
                ->post(route('branches.store'), $this->branchPayload(['code' => 'B' . $i, 'name' => 'Branch ' . $i]))
                ->assertRedirect(route('branches.index'));
        }

        $this->assertSame(3, Branch::where('company_id', $company->id)->count());
        $this->assertSame(3, $company->fresh()->branch_count);
    }

    public function test_zero_limit_blocks_everything_until_raised(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 0]);
        $this->assign($user, $company);

        $this->actingCompany($user, $company)
            ->post(route('branches.store'), $this->branchPayload())
            ->assertSessionHasErrors('branch_limit');

        $this->assertSame(0, Branch::where('company_id', $company->id)->count());

        $company->update(['branch_limit' => 2]);

        $this->actingCompany($user, $company)
            ->post(route('branches.store'), $this->branchPayload())
            ->assertRedirect(route('branches.index'));

        $this->assertSame(1, Branch::where('company_id', $company->id)->count());
    }

    public function test_branch_writes_require_the_company_admin_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(); // no role
        $company = $this->makeCompany();
        $this->assign($user, $company);

        $this->actingAs($user)->withSession(['current_company_id' => $company->id])
            ->post(route('branches.store'), $this->branchPayload())
            ->assertForbidden();

        $this->assertSame(0, Branch::where('company_id', $company->id)->count());
    }

    public function test_toggling_a_branch_from_another_company_is_forbidden(): void
    {
        $user = User::factory()->create();
        $companyA = $this->makeCompany('ACME');
        $companyB = $this->makeCompany('BETA');
        $companyA->update(['branch_limit' => null]);
        $companyB->update(['branch_limit' => null]);
        $this->assign($user, $companyA);

        $foreignBranch = Branch::create([
            'company_id' => $companyB->id,
            'name' => 'BETA HQ',
            'code' => 'BHQ',
            'is_active' => true,
        ]);

        $this->actingCompany($user, $companyA)
            ->patch(route('branches.toggle', $foreignBranch))
            ->assertForbidden();

        $this->assertTrue($foreignBranch->fresh()->is_active);
    }

    public function test_branches_index_renders_with_usage_and_form(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 5]);
        $this->assign($user, $company);

        Branch::create(['company_id' => $company->id, 'name' => 'HQ', 'code' => 'HQ', 'is_active' => true]);

        $this->actingCompany($user, $company)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertSee('Add Branch')
            ->assertSee('HQ');
    }

    public function test_usage_endpoint_reports_the_current_usage(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 5]);
        $this->assign($user, $company);

        Branch::create(['company_id' => $company->id, 'name' => 'A', 'code' => 'A', 'is_active' => true]);
        Branch::create(['company_id' => $company->id, 'name' => 'B', 'code' => 'B', 'is_active' => true]);

        $response = $this->actingCompany($user, $company)
            ->getJson(route('branches.usage'));

        $response->assertOk()
            ->assertJson([
                'branch_limit' => 5,
                'branch_count' => 2,
            ]);
    }

    public function test_stale_cached_count_self_heals_before_enforcement(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 1, 'branch_count' => 0]);

        Branch::create([
            'company_id' => $company->id,
            'name' => 'Existing',
            'code' => 'EXI',
            'is_active' => true,
        ]);

        $this->assign($user, $company);

        // Cached count is stale (0) but the live count is 1 == limit; the
        // reconcile step must block this second create instead of letting two
        // branches exceed a limit of 1.
        $this->actingCompany($user, $company)
            ->post(route('branches.store'), $this->branchPayload(['code' => 'NEW']))
            ->assertSessionHasErrors('branch_limit');

        $this->assertSame(1, Branch::where('company_id', $company->id)->count());

        // The reconcile save happened inside the failed transaction and rolled
        // back with it; the authoritative usage read self-heals the cached count.
        $this->assertSame(0, (int) $company->fresh()->branch_count);
        $this->assertSame(1, app(BranchLimitService::class)->usage($company)['branch_count']);
    }

    public function test_create_branch_is_atomic_and_serialized_by_the_central_lock(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 2]);
        $this->assign($user, $company);

        $service = app(BranchLimitService::class);

        // Simulate two near-simultaneous creates from the same company. The
        // central lockForUpdate + reconcile means the second create sees the
        // incremented count.
        $a = $service->createBranch($company, ['name' => 'A', 'code' => 'A'], $user, false);
        $b = $service->createBranch($company, ['name' => 'B', 'code' => 'B'], $user, false);

        $this->assertNotNull($a->id);
        $this->assertNotNull($b->id);
        $this->assertSame(2, $company->fresh()->branch_count);
        $this->assertSame(2, BranchAuditLog::where('company_id', $company->id)->count());

        $this->expectException(BranchLimitExceededException::class);
        $service->createBranch($company, ['name' => 'C', 'code' => 'C'], $user, false);
    }

    public function test_audit_log_records_manager_role_and_override_flags(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $company->update(['branch_limit' => 4]);
        $this->assign($user, $company);

        $this->actingCompany($user, $company)
            ->post(route('branches.store'), $this->branchPayload(['code' => 'MGR']))
            ->assertRedirect(route('branches.index'));

        $log = BranchAuditLog::where('company_id', $company->id)->firstOrFail();
        $this->assertSame(BranchAuditLog::ROLE_COMPANY_MANAGER, $log->created_by_role);
        $this->assertFalse($log->was_override);
        $this->assertSame(4, $log->branch_limit_at_creation);
        $this->assertSame(0, $log->branch_count_at_creation);
        $this->assertNotNull($log->created_at);
        $this->assertNull($log->updated_at);
    }

    public function test_self_serve_company_creation_requires_branch_limit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [
                'name' => 'Zulu Trading',
                'company_code' => 'ZULU',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
            ])
            ->assertSessionHasErrors('branch_limit');

        $this->assertDatabaseMissing('companies', ['company_code' => 'ZULU']);
    }

    public function test_self_serve_company_creation_rejects_negative_branch_limit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [
                'name' => 'Zulu Trading',
                'company_code' => 'ZULU',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'branch_limit' => -1,
            ])
            ->assertSessionHasErrors('branch_limit');

        $this->assertDatabaseMissing('companies', ['company_code' => 'ZULU']);
    }

    public function test_pre_existing_companies_keep_an_unlimited_limit(): void
    {
        $company = Company::create([
            'company_code' => 'LEGACY',
            'name' => 'Legacy Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => Company::STATUS_PENDING,
        ]);

        // The migration does not backfill limits: NULL stays NULL = unlimited.
        $this->assertNull($company->fresh()->branch_limit);
        $this->assertSame(0, (int) $company->fresh()->branch_count);
    }
}
