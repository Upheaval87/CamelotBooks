<?php

namespace App\Services\Search;

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\InventoryStock;
use App\Models\PayrollRun;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FeatureManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Registry of searchable entity types shared by the scoped inline
 * search fields (Mode 1) and the global search modal (Mode 2).
 *
 * Every entry exposes a `search` closure that must return a collection
 * of rows shaped as ['id', 'label', 'subtitle', 'url', ...extra].
 */
class SearchCatalog
{
    public function entries(): array
    {
        return [
            $this->product(),
            $this->account(),
            $this->customer(),
            $this->vendor(),
            $this->branch(),
            $this->costCenter(),
            $this->employee(),
            $this->user(),
            $this->bankAccount(),
            $this->asset(),
            $this->assetCategory(),
            $this->payrollRun(),
            $this->fiscalYear(),
        ];
    }

    public function keys(): array
    {
        return array_column($this->entries(), 'key');
    }

    public function find(string $key): ?array
    {
        foreach ($this->entries() as $entry) {
            if ($entry['key'] === $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Entries the given user may search in the active company:
     * feature gate (if any) must be enabled and the user must hold the
     * entity's view permission. Super-admins pass via User::can().
     */
    public function permittedFor(User $user, int $companyId): array
    {
        return array_values(array_filter(
            $this->entries(),
            function (array $entry) use ($user, $companyId): bool {
                if ($entry['feature'] && !FeatureManagement::isEnabled($companyId, $entry['feature'])) {
                    return false;
                }

                return $user->can($entry['permission']);
            }
        ));
    }

    private function matchColumns(Builder $query, array $columns, string $q): Builder
    {
        return $query->when($q !== '', function (Builder $w) use ($columns, $q) {
            $w->where(function (Builder $inner) use ($columns, $q) {
                foreach ($columns as $column) {
                    $inner->orWhere($column, 'like', "%{$q}%");
                }
            });
        });
    }

    private function product(): array
    {
        return [
            'key' => 'product',
            'label' => 'Products',
            'permission' => 'products.view',
            'feature' => null,
            'icon' => 'box',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                $products = $this->matchColumns(
                    Product::forCompany($companyId)
                        ->active()
                        ->select(['id', 'name', 'sku', 'barcode', 'sales_price', 'purchase_price', 'description', 'tax_rate', 'income_account_id', 'type', 'tracked_as_inventory']),
                    ['name', 'sku', 'barcode', 'description'],
                    $q
                )->orderBy('name')->limit($limit)->get();

                $ids = $products->pluck('id')->all();
                $stock = collect();
                if (!empty($ids)) {
                    $stock = InventoryStock::where('company_id', $companyId)
                        ->whereIn('product_id', $ids)
                        ->selectRaw('product_id, SUM(quantity_on_hand) as qty')
                        ->groupBy('product_id')
                        ->pluck('qty', 'product_id');
                }

                return $products->map(function (Product $p) use ($stock): array {
                    return [
                        'id' => $p->id,
                        'label' => $p->name,
                        'subtitle' => collect([$p->sku, $p->type])->filter()->implode(' · '),
                        'sku' => $p->sku,
                        'barcode' => $p->barcode,
                        'sales_price' => $p->sales_price,
                        'unit_price' => $p->sales_price,
                        'purchase_price' => $p->purchase_price,
                        'description' => $p->description,
                        'tax_rate' => $p->tax_rate,
                        'income_account_id' => $p->income_account_id,
                        'stock_qty' => $p->tracked_as_inventory ? (float) ($stock[$p->id] ?? 0) : null,
                        'url' => route('accounting.products.show', $p->id),
                    ];
                })->values();
            },
        ];
    }

    private function account(): array
    {
        return [
            'key' => 'account',
            'label' => 'Accounts',
            'permission' => 'chart-of-accounts.view',
            'feature' => null,
            'icon' => 'chart',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Account::forCompany($companyId)
                        ->active()
                        ->select(['id', 'code', 'name', 'type']),
                    ['name', 'code'],
                    $q
                )->orderBy('code')->limit($limit)->get()
                    ->map(fn (Account $a) => [
                        'id' => $a->id,
                        'label' => $a->name,
                        'subtitle' => trim($a->code.' · '.ucfirst(str_replace('_', ' ', $a->type))),
                        'code' => $a->code,
                        'url' => route('accounting.accounts.show', $a->id),
                    ])->values();
            },
        ];
    }

    private function customer(): array
    {
        return [
            'key' => 'customer',
            'label' => 'Customers',
            'permission' => 'customers.view',
            'feature' => null,
            'icon' => 'user',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Customer::forCompany($companyId)
                        ->active()
                        ->select(['id', 'name', 'email', 'phone', 'tin']),
                    ['name', 'email', 'phone', 'tin'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (Customer $c) => [
                        'id' => $c->id,
                        'label' => $c->name,
                        'subtitle' => collect([$c->email, $c->phone])->filter()->implode(' · '),
                        'url' => route('accounting.customers.show', $c->id),
                    ])->values();
            },
        ];
    }

    private function vendor(): array
    {
        return [
            'key' => 'vendor',
            'label' => 'Vendors',
            'permission' => 'vendors.view',
            'feature' => null,
            'icon' => 'truck',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Vendor::forCompany($companyId)
                        ->active()
                        ->select(['id', 'name', 'email', 'phone']),
                    ['name', 'email', 'phone'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (Vendor $v) => [
                        'id' => $v->id,
                        'label' => $v->name,
                        'subtitle' => collect([$v->email, $v->phone])->filter()->implode(' · '),
                        'url' => route('accounting.vendors.show', $v->id),
                    ])->values();
            },
        ];
    }

    private function branch(): array
    {
        return [
            'key' => 'branch',
            'label' => 'Branches',
            'permission' => 'branches.view',
            'feature' => null,
            'icon' => 'building',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Branch::where('company_id', $companyId)
                        ->where('is_active', true)
                        ->select(['id', 'name', 'code']),
                    ['name', 'code'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (Branch $b) => [
                        'id' => $b->id,
                        'label' => $b->name,
                        'subtitle' => $b->code,
                        'url' => route('branches.index'),
                    ])->values();
            },
        ];
    }

    private function costCenter(): array
    {
        return [
            'key' => 'cost-center',
            'label' => 'Cost Centers',
            'permission' => 'cost-centers.view',
            'feature' => null,
            'icon' => 'tag',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    CostCenter::forCompany($companyId)
                        ->active()
                        ->select(['id', 'name', 'code', 'description']),
                    ['name', 'code', 'description'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (CostCenter $c) => [
                        'id' => $c->id,
                        'label' => $c->name,
                        'subtitle' => $c->code,
                        'url' => route('accounting.cost-centers.index'),
                    ])->values();
            },
        ];
    }

    private function employee(): array
    {
        return [
            'key' => 'employee',
            'label' => 'Employees',
            'permission' => 'employees.view',
            'feature' => null,
            'icon' => 'user',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Employee::forCompany($companyId)
                        ->active()
                        ->select(['id', 'first_name', 'last_name', 'employee_number', 'email']),
                    ['first_name', 'last_name', 'employee_number', 'email'],
                    $q
                )->orderBy('first_name')->orderBy('last_name')->limit($limit)->get()
                    ->map(fn (Employee $e) => [
                        'id' => $e->id,
                        'label' => trim($e->first_name.' '.$e->last_name),
                        'subtitle' => collect([$e->employee_number, $e->email])->filter()->implode(' · '),
                        'url' => route('accounting.employees.show', $e->id),
                    ])->values();
            },
        ];
    }

    private function user(): array
    {
        return [
            'key' => 'user',
            'label' => 'Users',
            'permission' => 'users.view',
            'feature' => null,
            'icon' => 'users',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    User::whereHas('companies', fn (Builder $rel) => $rel->where('company_id', $companyId))
                        ->select(['id', 'name', 'email']),
                    ['name', 'email'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (User $u) => [
                        'id' => $u->id,
                        'label' => $u->name,
                        'subtitle' => $u->email,
                        'url' => route('admin.users.edit', $u->id),
                    ])->values();
            },
        ];
    }

    private function bankAccount(): array
    {
        return [
            'key' => 'bank-account',
            'label' => 'Bank Accounts',
            'permission' => 'bank-accounts.view',
            'feature' => 'banking',
            'icon' => 'bank',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Account::forCompany($companyId)
                        ->active()
                        ->where('is_bank_account', true)
                        ->select(['id', 'code', 'name', 'type']),
                    ['name', 'code'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (Account $a) => [
                        'id' => $a->id,
                        'label' => $a->name,
                        'subtitle' => $a->code,
                        'url' => route('accounting.bank-accounts.register', $a->id),
                    ])->values();
            },
        ];
    }

    private function asset(): array
    {
        return [
            'key' => 'asset',
            'label' => 'Fixed Assets',
            'permission' => 'fixed-assets.view',
            'feature' => 'fixed_assets',
            'icon' => 'archive',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    Asset::forCompany($companyId)
                        ->active()
                        ->select(['id', 'asset_code', 'name', 'serial_number']),
                    ['name', 'asset_code', 'serial_number'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (Asset $a) => [
                        'id' => $a->id,
                        'label' => $a->name,
                        'subtitle' => collect([$a->asset_code, $a->serial_number])->filter()->implode(' · '),
                        'url' => route('accounting.fixed-assets.show', $a->id),
                    ])->values();
            },
        ];
    }

    private function assetCategory(): array
    {
        return [
            'key' => 'asset-category',
            'label' => 'Asset Categories',
            'permission' => 'asset-categories.view',
            'feature' => 'fixed_assets',
            'icon' => 'tag',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    AssetCategory::forCompany($companyId)
                        ->active()
                        ->select(['id', 'code', 'name']),
                    ['name', 'code'],
                    $q
                )->orderBy('name')->limit($limit)->get()
                    ->map(fn (AssetCategory $c) => [
                        'id' => $c->id,
                        'label' => $c->name,
                        'subtitle' => $c->code,
                        'url' => route('accounting.asset-categories.show', $c->id),
                    ])->values();
            },
        ];
    }

    private function payrollRun(): array
    {
        return [
            'key' => 'payroll-run',
            'label' => 'Payroll Runs',
            'permission' => 'payroll-runs.view',
            'feature' => 'payroll',
            'icon' => 'currency',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    PayrollRun::forCompany($companyId)
                        ->select(['id', 'run_number', 'period_label', 'status']),
                    ['run_number', 'period_label'],
                    $q
                )->orderBy('run_number')->limit($limit)->get()
                    ->map(fn (PayrollRun $r) => [
                        'id' => $r->id,
                        'label' => $r->run_number,
                        'subtitle' => collect([$r->period_label, $r->status])->filter()->implode(' · '),
                        'url' => route('accounting.payroll-runs.show', $r->id),
                    ])->values();
            },
        ];
    }

    private function fiscalYear(): array
    {
        return [
            'key' => 'fiscal-year',
            'label' => 'Fiscal Years',
            'permission' => 'fiscal-years.view',
            'feature' => null,
            'icon' => 'calendar',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return $this->matchColumns(
                    FiscalYear::forCompany($companyId)
                        ->select(['id', 'label', 'status']),
                    ['label'],
                    $q
                )->orderBy('label')->limit($limit)->get()
                    ->map(fn (FiscalYear $f) => [
                        'id' => $f->id,
                        'label' => $f->label,
                        'subtitle' => $f->status,
                        'url' => route('accounting.fiscal-years.show', $f->id),
                    ])->values();
            },
        ];
    }
}
