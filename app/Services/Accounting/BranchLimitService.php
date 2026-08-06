<?php

namespace App\Services\Accounting;

use App\Models\Branch;
use App\Models\BranchAuditLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Support\Facades\DB;

/**
 * Branch-limit enforcement.
 *
 * Design decisions (documented per the feature spec):
 *
 * - `branch_count` is a DENORMALIZED cached count of ACTIVE branches, but the
 *   authoritative value is always the live count (liveCount()). Every
 *   enforcement and usage read reconciles the cached column against the live
 *   count, so pre-existing companies (whose cached count starts at 0 from the
 *   migration) self-heal on first use.
 * - Concurrency: creation is serialized per company with a
 *   `lockForUpdate()` on the CENTRAL companies row inside a transaction. The
 *   central row lock works even though branch rows live in the tenant
 *   database (or the shared DB for legacy companies) because every creator
 *   takes the same lock before re-reading the count.
 * - `branch_limit = NULL` means unlimited and never blocks. `branch_limit =
 *   0` blocks everything until raised.
 * - The override bypass is a server-side flag: the client may SEND
 *   `override=true`, but it only takes effect when the authenticated actor is
 *   verified as a super admin (User::isSuperAdmin). A client-supplied role
 *   claim is never trusted.
 */
class BranchLimitService
{
    public function __construct(private readonly TenantConnectionResolver $resolver)
    {
    }

    /**
     * Live count of ACTIVE branches. The caller must be in a context where the
     * tenant connection is bound (provisioned companies) or where branches live
     * on the default connection (legacy shared-DB companies); both are true for
     * every tenant request.
     */
    public function liveCount(Company $company): int
    {
        return Branch::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Current usage for display: ['branch_limit' => ?int, 'branch_count' => int].
     * Works from BOTH the tenant context (already bound) and the Super Admin
     * context (binds the tenant connection temporarily and clears it, so no
     * tenant binding leaks into the panel request).
     */
    public function usage(Company $company): array
    {
        $wasBound = $this->resolver->isBound();
        $shouldBind = $company->isProvisioned() && $company->is_active && !$wasBound;

        if ($shouldBind) {
            try {
                $this->resolver->resolve($company);
            } catch (\Throwable) {
                return [
                    'branch_limit' => $company->branch_limit,
                    'branch_count' => (int) ($company->branch_count ?? 0),
                ];
            }
        }

        try {
            $live = $this->liveCount($company);

            if ((int) ($company->branch_count ?? 0) !== $live) {
                $company->forceFill(['branch_count' => $live])->save();
            }

            return [
                'branch_limit' => $company->branch_limit,
                'branch_count' => $live,
            ];
        } finally {
            if ($shouldBind) {
                $this->resolver->clear();
            }
        }
    }

    /**
     * Enforce the limit. Throws BranchLimitExceededException when blocked.
     * An override is honoured ONLY when $override is true AND $actor is a
     * verified super admin.
     */
    public function assertCanCreate(Company $company, bool $override, ?User $actor = null): void
    {
        if ($override && $actor?->isSuperAdmin()) {
            return;
        }

        if ($company->branch_limit === null) {
            return; // NULL = unlimited
        }

        if ((int) $company->branch_count < (int) $company->branch_limit) {
            return;
        }

        throw new BranchLimitExceededException($company->branch_limit, (int) $company->branch_count);
    }

    /**
     * Create a branch atomically: serialized by a central company-row lock,
     * cached count reconciled, limit enforced, branch inserted, cached count
     * incremented, and an immutable branch_audit_log row written.
     */
    public function createBranch(Company $company, array $data, User $user, bool $wasOverride): Branch
    {
        return DB::transaction(function () use ($company, $data, $user, $wasOverride) {
            // Serialize concurrent creates from the same company.
            $locked = Company::query()->lockForUpdate()->findOrFail($company->id);

            // Self-heal stale cached counts (pre-migration companies start at 0).
            $live = $this->liveCount($locked);
            if ((int) $locked->branch_count !== $live) {
                $locked->forceFill(['branch_count' => $live])->save();
            }

            $this->assertCanCreate($locked, $wasOverride, $user);

            $branch = Branch::create($data + [
                'company_id' => $locked->id,
                'is_active' => true,
            ]);

            // Snapshot the count at the moment of creation BEFORE incrementing,
            // so the audit log records the same number the enforcement saw.
            $countAtCreation = (int) $locked->branch_count;

            $locked->increment('branch_count');

            BranchAuditLog::create([
                'branch_id' => $branch->id,
                'company_id' => $locked->id,
                'created_by_user_id' => $user->id,
                'created_by_role' => $this->roleFor($user),
                'was_override' => $wasOverride,
                'branch_limit_at_creation' => $locked->branch_limit,
                'branch_count_at_creation' => $countAtCreation,
                'created_at' => now(),
            ]);

            return $branch;
        });
    }

    /**
     * The audited creator role label. A central super admin OR a holder of the
     * platform system_admin role counts as super_admin; anything else that
     * reaches creation (company_admin) is a company_manager.
     */
    public function roleFor(User $user): string
    {
        return ($user->isSuperAdmin() || $user->hasRole('system_admin'))
            ? BranchAuditLog::ROLE_SUPER_ADMIN
            : BranchAuditLog::ROLE_COMPANY_MANAGER;
    }
}
