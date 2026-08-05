<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_core',
        'sort_order',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }
}
