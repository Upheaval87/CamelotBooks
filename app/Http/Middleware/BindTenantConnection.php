<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\Tenancy\TenantConnectionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the runtime `tenant` connection for the session's selected company and
 * server-side re-verifies the assignment on EVERY request:
 *
 *  - a session company the user no longer has (forged/mismatched, deactivated
 *    assignment, or removed company_user row) is rejected;
 *  - a session company that is no longer provisioned/active is rejected;
 *  - when no company is selected (super admins in the Panel, or users on the
 *    picker), NO tenant connection is bound and the request passes through.
 *
 * Must run BEFORE company.context/company.active and any tenant query.
 */
class BindTenantConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $companyId = session('current_company_id');

        // Legacy fallback: the trusted `current_company_id` column is only ever
        // set server-side (login auto-select / explicit select). It is still
        // re-verified below before any tenant connection is bound.
        if (!$companyId && $user->current_company_id) {
            $companyId = $user->current_company_id;
        }

        if (!$companyId) {
            return $next($request);
        }

        if (!$user->hasAccessToCompany((int) $companyId)) {
            session()->forget('current_company_id');

            return redirect()
                ->route('companies.index')
                ->with('error', 'You no longer have access to that company.');
        }

        $company = Company::query()->find((int) $companyId);

        if (!$company || !$company->is_active) {
            session()->forget('current_company_id');

            return redirect()
                ->route('companies.index')
                ->with('error', 'The selected company is no longer available.');
        }

        // Provisioned companies get the tenant connection bound. Unprovisioned
        // companies run in legacy mode (data still in the shared DB) — no binding.
        if ($company->isProvisioned()) {
            app(TenantConnectionResolver::class)->resolve($company);
        }

        setPermissionsTeamId($company->id);

        session(['current_company_id' => (int) $companyId]);

        return $next($request);
    }
}
