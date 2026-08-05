<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-level guard. Only central super admins may access the panel; a
 * tenant-side role/permission is irrelevant here (never scoped by company).
 */
class SuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin() || !$user->is_active) {
            abort(403);
        }

        return $next($request);
    }
}
