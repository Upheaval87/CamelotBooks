<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SegregationOfDuty
{
    public function handle(Request $request, Closure $next, string $routeParameter): Response
    {
        $record = $request->route($routeParameter);

        if (!$record || !method_exists($record, 'getAttribute')) {
            return $next($request);
        }

        $createdBy = $record->getAttribute('created_by');

        if ($createdBy && (int) $createdBy === (int) auth()->id()) {
            abort(403, __('You cannot perform this action on a record you created.'));
        }

        return $next($request);
    }
}
