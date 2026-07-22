<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = session('current_company_id');

        if (!$companyId) {
            return redirect()->route('companies.index');
        }

        $company = Company::find($companyId);

        if (!$company || !$company->is_active) {
            session()->forget('current_company_id');
            return redirect()->route('companies.index')->with('error', 'The selected company is no longer available.');
        }

        return $next($request);
    }
}
