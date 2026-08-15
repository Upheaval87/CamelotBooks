@php
    $product = $product ?? null;
    $isEdit = $isEdit ?? (bool) $product;
    $formAction = $formAction ?? ($isEdit ? route('accounting.products.update', $product) : route('accounting.products.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? route('accounting.products.index');
    $title = $title ?? ($isEdit ? __('Edit Product') : __('Create Product'));
    $subtitle = $subtitle ?? 'Set up the item details, pricing, accounts and tax for this product.';
    $submitLabel = $submitLabel ?? ($isEdit ? __('Update Product') : __('Create Product'));

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

    $type = $isEdit ? old('type', $product->type) : old('type', 'inventory');
    $types = [
        'service' => 'Service',
        'inventory' => 'Inventory',
        'non_inventory' => 'Non-Inventory',
    ];
@endphp

<div class="suite">

    {{-- sticky page head --}}
    <div class="sticky-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="sub">{{ $subtitle }}</div>
        </div>
        <div class="tbtns">
            @if($isEdit)
                <form method="POST" action="{{ route('accounting.products.toggle', $product) }}" id="product-archive-form" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this product?') }}', { type: 'danger' })">
                    @csrf @method('PATCH')
                </form>
                <button type="submit" form="product-archive-form" class="btn danger-o sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                    {{ $product->is_active ? __('Deactivate') : __('Activate') }}
                </button>
            @endif
            <a href="{{ $cancelRoute }}" class="btn ghost sm">{{ __('Cancel') }}</a>
            <button type="submit" form="product-form" class="btn cta">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="product-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="shell">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- product details --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                        <h2>Product Details</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="name">Name <span class="req">*</span></label>
                            <input id="name" name="name" type="text" class="input" value="{{ $isEdit ? old('name', $product->name) : old('name') }}" placeholder="e.g. Wireless Mouse" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="type">Type <span class="req">*</span></label>
                            <select id="type" name="type" class="input" required>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="sku">Stock Keeping Unit (SKU)</label>
                            <input id="sku" name="sku" type="text" class="input" value="{{ $isEdit ? old('sku', $product->sku) : old('sku') }}" placeholder="e.g. WM-001" />
                            <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="barcode">Barcode</label>
                            <input id="barcode" name="barcode" type="text" class="input" value="{{ $isEdit ? old('barcode', $product->barcode) : old('barcode') }}" placeholder="e.g. 5051234567890" />
                            <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                        </div>
                        <div class="field sp4">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="2" class="input">{{ $isEdit ? old('description', $product->description) : old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- pricing --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h2>Pricing</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field">
                            <label for="sales_price">Sales Price ({{ $cs }})</label>
                            <input id="sales_price" name="sales_price" type="number" step="0.01" min="0" class="input" value="{{ $isEdit ? old('sales_price', $product->sales_price) : old('sales_price') }}" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('sales_price')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="purchase_price">Purchase Price ({{ $cs }})</label>
                            <input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" class="input" value="{{ $isEdit ? old('purchase_price', $product->purchase_price) : old('purchase_price') }}" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('purchase_price')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="tax_rate">Tax Rate (%)</label>
                            <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="input" value="{{ $isEdit ? old('tax_rate', $product->tax_rate) : old('tax_rate') }}" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('tax_rate')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label class="label">Taxable</label>
                            <div class="val" style="padding-top:11px">
                                <label class="chk-inline" style="display:flex;align-items:center;gap:8px">
                                    <input type="checkbox" name="is_taxable" value="1" {{ $isEdit ? (old('is_taxable', $product->is_taxable) ? 'checked' : '') : (old('is_taxable') ? 'checked' : 'checked') }} class="rounded border-gray-300 text-gold-700 focus:ring-gold-500" />
                                    <span class="hint" style="margin-top:0;font-weight:500;color:var(--muted,#5F7476)">Charge sales tax on this product</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('is_taxable')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- accounts & tax --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                        <h2>Accounts &amp; Tax</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="income_account_id">Income Account</label>
                            <x-scoped-search-field
                                name="income_account_id"
                                entity="account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                :value="$isEdit ? old('income_account_id', $product->income_account_id) : old('income_account_id')"
                                :label="$isEdit ? (($incomeAccounts->firstWhere('id', (int) old('income_account_id', $product->income_account_id))) ? $incomeAccounts->firstWhere('id', (int) old('income_account_id', $product->income_account_id))->code . ' - ' . $incomeAccounts->firstWhere('id', (int) old('income_account_id', $product->income_account_id))->name : '') : (($incomeAccounts->firstWhere('id', (int) old('income_account_id'))) ? $incomeAccounts->firstWhere('id', (int) old('income_account_id'))->code . ' - ' . $incomeAccounts->firstWhere('id', (int) old('income_account_id'))->name : '')"
                                placeholder="{{ __('None') }}"
                            />
                            <x-input-error :messages="$errors->get('income_account_id')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="expense_account_id">Expense Account</label>
                            <x-scoped-search-field
                                name="expense_account_id"
                                entity="account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                :value="$isEdit ? old('expense_account_id', $product->expense_account_id) : old('expense_account_id')"
                                :label="$isEdit ? (($expenseAccounts->firstWhere('id', (int) old('expense_account_id', $product->expense_account_id))) ? $expenseAccounts->firstWhere('id', (int) old('expense_account_id', $product->expense_account_id))->code . ' - ' . $expenseAccounts->firstWhere('id', (int) old('expense_account_id', $product->expense_account_id))->name : '') : (($expenseAccounts->firstWhere('id', (int) old('expense_account_id'))) ? $expenseAccounts->firstWhere('id', (int) old('expense_account_id'))->code . ' - ' . $expenseAccounts->firstWhere('id', (int) old('expense_account_id'))->name : '')"
                                placeholder="{{ __('None') }}"
                            />
                            <x-input-error :messages="$errors->get('expense_account_id')" class="mt-2" />
                        </div>
                    </div>
                </section>
            </div>

            {{-- right rail --}}
            <aside>
                <div class="railsum">
                    <div class="card">
                        <div class="rail-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                                <h2>Quick Nav</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="vlist">
                                @if($isEdit)
                                    <a href="{{ route('accounting.products.show', $product) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                                        View Product
                                    </a>
                                    <a href="{{ route('accounting.stock-adjustments.create', ['product_id' => $product->id]) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg></span>
                                        Adjust Stock
                                    </a>
                                    <a href="{{ route('accounting.stock-transfers.create', ['product_id' => $product->id]) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg></span>
                                        Transfer Stock
                                    </a>
                                @endif
                                <a href="{{ route('accounting.products.index') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                                    Products List
                                </a>
                                <a href="{{ route('accounting.purchase-orders.create') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg></span>
                                    New Purchase Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>
