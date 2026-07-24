<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id',
        'two_factor_secret',
        'two_factor_enabled',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function getRoleInCurrentCompanyAttribute(): ?string
    {
        $companyId = session('current_company_id');
        if (!$companyId) {
            return null;
        }
        $pivot = $this->companies()->where('company_id', $companyId)->first()?->pivot;
        return $pivot?->role;
    }

    public function hasRoleInCompany(string $role, ?int $companyId = null): bool
    {
        $companyId = $companyId ?? session('current_company_id');
        if (!$companyId) {
            return false;
        }
        return $this->companies()
            ->where('company_id', $companyId)
            ->where('company_user.role', $role)
            ->exists();
    }

    public function hasAnyRoleInCompany(array $roles, ?int $companyId = null): bool
    {
        $companyId = $companyId ?? session('current_company_id');
        if (!$companyId) {
            return false;
        }
        return $this->companies()
            ->where('company_id', $companyId)
            ->whereIn('company_user.role', $roles)
            ->exists();
    }

    public function canApproveInCompany(?int $companyId = null): bool
    {
        return $this->hasAnyRoleInCompany(['company_admin', 'approver', 'system_admin'], $companyId);
    }

    public function canManagePeriodsInCompany(?int $companyId = null): bool
    {
        return $this->hasAnyRoleInCompany(['company_admin', 'system_admin'], $companyId);
    }

    public function getActiveCompanyId(): ?int
    {
        return session('current_company_id') ?? $this->current_company_id;
    }
}
