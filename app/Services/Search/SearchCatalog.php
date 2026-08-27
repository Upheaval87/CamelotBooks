<?php

namespace App\Services\Search;

use App\Models\Account;
use App\Models\FixedAssets\FaAsset as Asset;
use App\Models\FixedAssets\FaCategory as AssetCategory;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SalesReceipt;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCredit;
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
            $this->user(),
            $this->bankAccount(),
            // asset + asset-category entries rebuilt in Phase 4
            $this->fiscalYear(),
            $this->invoice(),
            $this->bill(),
            $this->salesReceipt(),
            $this->quotation(),
            $this->salesOrder(),
            $this->creditNote(),
            $this->vendorCredit(),
            $this->budget(),
            $this->employee(),
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
                        ->select(['id', 'name', 'sku', 'barcode', 'sales_price', 'purchase_price', 'description', 'tax_rate', 'income_account_id', 'expense_account_id', 'type', 'tracked_as_inventory']),
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
                        'expense_account_id' => $p->expense_account_id,
                        'stock_qty' => $p->tracked_as_inventory ? (float) ($stock[$p->id] ?? 0) : null,
                        'url' => route('accounting.inventory.items.show', $p->id),
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
                        'email' => $c->email,
                        'phone' => $c->phone,
                        'display_name' => $c->display_name,
                        'payment_terms' => $c->payment_terms,
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
                        'url' => route('accounting.banking.register', $a->id),
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
                        'url' => route('accounting.fixed-assets.categories'),
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

    private function invoice(): array
    {
        return [
            'key' => 'invoice',
            'label' => 'Invoices',
            'permission' => 'invoices.view',
            'feature' => null,
            'icon' => 'currency',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return Invoice::forCompany($companyId)
                    ->with('customer:id,name')
                    ->select(['id', 'invoice_number', 'invoice_date', 'status', 'customer_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('invoice_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('invoice_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Invoice $i) => [
                        'id' => $i->id,
                        'label' => $i->invoice_number,
                        'subtitle' => collect([$i->customer?->name, $i->invoice_date?->format('M d, Y'), $i->status])->filter()->implode(' · '),
                        'url' => route('accounting.invoices.show', $i->id),
                    ])->values();
            },
        ];
    }

    private function bill(): array
    {
        return [
            'key' => 'bill',
            'label' => 'Bills',
            'permission' => 'bills.view',
            'feature' => null,
            'icon' => 'bank',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return Bill::forCompany($companyId)
                    ->with('vendor:id,name')
                    ->select(['id', 'bill_number', 'bill_date', 'status', 'vendor_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('bill_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('bill_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Bill $b) => [
                        'id' => $b->id,
                        'label' => $b->bill_number,
                        'subtitle' => collect([$b->vendor?->name, $b->bill_date?->format('M d, Y'), $b->status])->filter()->implode(' · '),
                        'url' => route('accounting.bills.show', $b->id),
                    ])->values();
            },
        ];
    }

    private function salesReceipt(): array
    {
        return [
            'key' => 'sales-receipt',
            'label' => 'Sales Receipts',
            'permission' => 'sales-receipts.view',
            'feature' => null,
            'icon' => 'archive',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return SalesReceipt::forCompany($companyId)
                    ->with('customer:id,name')
                    ->select(['id', 'receipt_number', 'receipt_date', 'status', 'customer_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('receipt_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('receipt_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (SalesReceipt $r) => [
                        'id' => $r->id,
                        'label' => $r->receipt_number,
                        'subtitle' => collect([$r->customer?->name, $r->receipt_date?->format('M d, Y'), $r->status])->filter()->implode(' · '),
                        'url' => route('accounting.sales-receipts.show', $r->id),
                    ])->values();
            },
        ];
    }

    private function quotation(): array
    {
        return [
            'key' => 'quotation',
            'label' => 'Quotations',
            'permission' => 'quotations.view',
            'feature' => null,
            'icon' => 'tag',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return Quotation::forCompany($companyId)
                    ->with('customer:id,name')
                    ->select(['id', 'quotation_number', 'quotation_date', 'status', 'customer_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('quotation_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('quotation_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Quotation $qo) => [
                        'id' => $qo->id,
                        'label' => $qo->quotation_number,
                        'subtitle' => collect([$qo->customer?->name, $qo->quotation_date?->format('M d, Y'), $qo->status])->filter()->implode(' · '),
                        'url' => route('accounting.quotations.show', $qo->id),
                    ])->values();
            },
        ];
    }

    private function salesOrder(): array
    {
        return [
            'key' => 'sales-order',
            'label' => 'Sales Orders',
            'permission' => 'sales-orders.view',
            'feature' => null,
            'icon' => 'tag',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return SalesOrder::forCompany($companyId)
                    ->with('customer:id,name')
                    ->select(['id', 'sales_order_number', 'order_date', 'status', 'customer_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('sales_order_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('order_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (SalesOrder $o) => [
                        'id' => $o->id,
                        'label' => $o->sales_order_number,
                        'subtitle' => collect([$o->customer?->name, $o->order_date?->format('M d, Y'), $o->status])->filter()->implode(' · '),
                        'url' => route('accounting.sales-orders.show', $o->id),
                    ])->values();
            },
        ];
    }

    private function creditNote(): array
    {
        return [
            'key' => 'credit-note',
            'label' => 'Credit Notes',
            'permission' => 'credit-notes.view',
            'feature' => null,
            'icon' => 'user',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return CreditNote::forCompany($companyId)
                    ->with('customer:id,name')
                    ->select(['id', 'credit_note_number', 'credit_note_date', 'status', 'customer_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('credit_note_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('credit_note_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (CreditNote $c) => [
                        'id' => $c->id,
                        'label' => $c->credit_note_number,
                        'subtitle' => collect([$c->customer?->name, $c->credit_note_date?->format('M d, Y'), $c->status])->filter()->implode(' · '),
                        'url' => route('accounting.credit-notes.show', $c->id),
                    ])->values();
            },
        ];
    }

    private function vendorCredit(): array
    {
        return [
            'key' => 'vendor-credit',
            'label' => 'Vendor Credits',
            'permission' => 'vendor-credits.view',
            'feature' => null,
            'icon' => 'truck',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return VendorCredit::forCompany($companyId)
                    ->with('vendor:id,name')
                    ->select(['id', 'credit_note_number', 'credit_note_date', 'status', 'vendor_id', 'reference'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('credit_note_number', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%")
                                ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$q}%"));
                        });
                    })
                    ->orderByDesc('credit_note_date')->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (VendorCredit $vc) => [
                        'id' => $vc->id,
                        'label' => $vc->credit_note_number,
                        'subtitle' => collect([$vc->vendor?->name, $vc->credit_note_date?->format('M d, Y'), $vc->status])->filter()->implode(' · '),
                        'url' => route('accounting.vendor-credits.show', $vc->id),
                    ])->values();
            },
        ];
    }

    private function budget(): array
    {
        return [
            'key' => 'budget',
            'label' => 'Budgets',
            'permission' => 'budgets.view',
            'feature' => 'budgets',
            'icon' => 'piggy-bank',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return Budget::forCompany($companyId)
                    ->with('fiscalYear:id,label')
                    ->select(['id', 'code', 'name', 'status', 'fiscal_year_id'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('code', 'like', "%{$q}%")
                                ->orWhere('name', 'like', "%{$q}%");
                        });
                    })
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Budget $b) => [
                        'id' => $b->id,
                        'label' => "{$b->code} — {$b->name}",
                        'subtitle' => collect([$b->fiscalYear?->label, $b->status])->filter()->implode(' · '),
                        'url' => route('accounting.budgets.show', $b->id),
                    ])->values();
            },
        ];
    }

    private function employee(): array
    {
        return [
            'key' => 'employee',
            'label' => 'Employees',
            'permission' => 'payroll-runs.view',
            'feature' => 'payroll',
            'icon' => 'users',
            'search' => function (string $q, int $companyId, int $limit, ?int $branchId = null): Collection {
                return Employee::forCompany($companyId)
                    ->select(['id', 'employee_number', 'first_name', 'last_name', 'email', 'department', 'position', 'is_active'])
                    ->when($q !== '', function (Builder $w) use ($q) {
                        $w->where(function (Builder $inner) use ($q) {
                            $inner->where('employee_number', 'like', "%{$q}%")
                                ->orWhere('first_name', 'like', "%{$q}%")
                                ->orWhere('last_name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                    })
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn (Employee $e) => [
                        'id' => $e->id,
                        'label' => $e->full_name,
                        'subtitle' => collect([$e->employee_number, $e->department, $e->position])->filter()->implode(' · '),
                        'url' => route('accounting.payroll.employees.show', $e->id),
                    ])->values();
            },
        ];
    }
}
