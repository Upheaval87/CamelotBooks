<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyBuild extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'assembly_product_id',
        'bom_id',
        'build_number',
        'type',
        'quantity',
        'total_component_cost',
        'unit_cost',
        'status',
        'memo',
        'journal_entry_id',
        'created_by',
        'date',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'total_component_cost' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assemblyProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'assembly_product_id');
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
