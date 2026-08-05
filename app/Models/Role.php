<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Company-scoped role. Resolves to the tenant connection when one is bound so
 * per-company role/permission data comes from the tenant database.
 */
class Role extends SpatieRole
{
    use TenantScoped;

}
