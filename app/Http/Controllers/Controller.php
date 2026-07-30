<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Abort with 403 unless the current user has the given permission(s).
     */
    protected function requirePermission(Request|string $requestOrPerm, ?string $permission = null): void
    {
        if ($requestOrPerm instanceof Request) {
            $user = $requestOrPerm->user();
            $perm = $permission;
        } else {
            $user = request()->user();
            $perm = $requestOrPerm;
        }

        abort_unless($user && $user->can($perm), 403, "Missing permission: {$perm}");
    }
}
