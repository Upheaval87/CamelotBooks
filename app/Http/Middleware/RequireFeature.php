<?php

namespace App\Http\Middleware;

use App\Services\FeatureManagement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $companyId = session('current_company_id');

        if (!$companyId || !FeatureManagement::isEnabled($companyId, $feature)) {
            abort(404);
        }

        return $next($request);
    }
}
