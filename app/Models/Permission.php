<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Company-scoped permission. Resolves to the tenant connection when one is
 * bound so per-company role/permission data comes from the tenant database.
 */
class Permission extends SpatiePermission
{
    use TenantScoped;

}
