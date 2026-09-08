<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BiAction extends Model
{
    use TenantScoped;

    protected $table = 'bi_actions';

    protected $fillable = [
        'company_id',
        'page',
        'description',
        'owner',
        'due_date',
        'status',
        'position',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'position' => 'integer',
    ];

    public function scopeForCompany($q, int $companyId)
    {
        return $q->where('company_id', $companyId);
    }
}