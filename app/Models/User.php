<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles {
        hasAnyRole as spatieHasAnyRole;
        hasRole as spatieHasRole;
        hasAllRoles as spatieHasAllRoles;
        hasExactRoles as spatieHasExactRoles;
    }

    protected $fillable = [
        'name',
        'email',
        'is_super_admin',
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
            'is_super_admin' => 'boolean',
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
        return $this->roles->first()?->name;
    }

    public function getActiveCompanyId(): ?int
    {
        return session('current_company_id') ?? $this->current_company_id;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function hasAnyRole(...$roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->spatieHasAnyRole(...$roles);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->spatieHasRole($roles, $guard);
    }

    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->spatieHasAllRoles($roles, $guard);
    }

    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->spatieHasExactRoles($roles, $guard);
    }

    public function can($ability, $arguments = []): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return parent::can($ability, $arguments);
    }
}
