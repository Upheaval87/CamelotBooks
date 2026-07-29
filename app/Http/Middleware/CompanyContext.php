<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class CompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = session('current_company_id');

        if (!$companyId && $request->user()) {
            $companyId = $request->user()->current_company_id;
            if ($companyId) {
                session(['current_company_id' => $companyId]);
            }
        }

        if ($companyId) {
            setPermissionsTeamId($companyId);

            $company = Company::with('branches')->find($companyId);

            if ($company && $company->is_active) {
                View::share('currentCompany', $company);
                View::share('currentBranches', $company->branches->where('is_active', true));
            }
        }

        View::share('userCompanies', $request->user()?->companies ?? collect());

        return $next($request);
    }
}
