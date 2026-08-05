<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Central. Immutable audit trail of every company entry: auto-select at login
 * (action = 'login'), explicit switch ('select'), and super-admin support access
 * ('support'). Never uses the TenantScoped trait.
 */
class CompanyAccessLog extends Model
{
    public const ACTION_LOGIN = 'login';

    public const ACTION_SELECT = 'select';

    public const ACTION_SUPPORT = 'support';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'company_id',
        'action',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
