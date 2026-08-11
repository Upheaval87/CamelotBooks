@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $stockQty = $product->stock->sum('quantity_on_hand');
@endphp

<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page head --}}
                <div class="page-head">
                    <div>
                        <h1>{{ __('Product Detail') }}</h1>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.products.index') }}" class="btn ghost sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            All Products
                        </a>
                    </div>
                </div>

                {{-- profile header --}}
                <div class="card">
                    <div class="prof">
                        <span class="ava-xl" style="border-radius:16px">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:26px;height:26px"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </span>
                        <div>
                            <div class="n">
                                {{ $product->name }}
                                @if ($product->is_active)
                                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                                @else
                                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                @endif
                            </div>
                            <div class="c">
                                @if ($product->sku)
                                    <span class="mono">{{ $product->sku }}</span>
                                @endif
                                @if ($product->category)
                                    <span>{{ $product->category->name }}</span>
                                @endif
                                <span><span class="tchip">{{ str_replace('_', ' ', ucfirst($product->type)) }}</span></span>
                            </div>
                        </div>
                        <div class="acts">
                            <a href="{{ route('accounting.products.edit', $product) }}" class="btn cta sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            @can('products.void')
                                <form method="POST" action="{{ route('accounting.products.toggle', $product) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this product?') }}', { type: 'danger' })">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn danger-o sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                                        {{ $product->is_active ? __('Deactivate') : __('Activate') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- stats --}}
                <div class="sgrid" style="margin-top:16px">
                    <div class="sbox">
                        <div class="l">On Hand</div>
                        <div class="v">{{ format_number($stockQty) }}</div>
                    </div>
                    <div class="sbox">
                        <div class="l">Sales Price ({{ $cs }})</div>
                        <div class="v">{{ format_number($product->sales_price ?? 0) }}</div>
                    </div>
                    <div class="sbox">
                        <div class="l">Purchase Price ({{ $cs }})</div>
                        <div class="v">{{ format_number($product->purchase_price ?? 0) }}</div>
                    </div>
                </div>

                <div class="shell" style="margin-top:22px">
                    <div class="flex flex-col gap-5 min-w-0">

                        {{-- product information --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                                <h2>Product Information</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g3">
                                <div class="field">
                                    <div class="label">SKU</div>
                                    <div class="val mono">{{ $product->sku ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Barcode</div>
                                    <div class="val mono">{{ $product->barcode ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Category</div>
                                    <div class="val">{{ $product->category?->name ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Unit of Measure</div>
                                    <div class="val">{{ $product->unit_of_measure ?? 'Each' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Tracked as Inventory</div>
                                    <div class="val">{{ $product->tracked_as_inventory ? __('Yes') : __('No') }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Assembly</div>
                                    <div class="val">{{ $product->is_assembly ? __('Yes') : __('No') }}</div>
                                </div>
                                @if($product->description)
                                    <div class="field sp3">
                                        <div class="label">Description</div>
                                        <div class="val">{{ $product->description }}</div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        {{-- accounts & tax --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                <h2>Accounts &amp; Tax</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g3">
                                <div class="field">
                                    <div class="label">Income Account</div>
                                    <div class="val">{{ $product->incomeAccount?->name ? "{$product->incomeAccount->code} - {$product->incomeAccount->name}" : '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Expense Account</div>
                                    <div class="val">{{ $product->expenseAccount?->name ? "{$product->expenseAccount->code} - {$product->expenseAccount->name}" : '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Inventory Asset Account</div>
                                    <div class="val">{{ $product->inventoryAssetAccount?->name ? "{$product->inventoryAssetAccount->code} - {$product->inventoryAssetAccount->name}" : '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Tax Rate</div>
                                    <div class="val">{{ format_number($product->tax_rate ?? 0) }}%</div>
                                </div>
                                <div class="field">
                                    <div class="label">Taxable</div>
                                    <div class="val">{{ $product->is_taxable ? __('Yes') : __('No') }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Reorder Point</div>
                                    <div class="val">{{ $product->effective_reorder_point !== null ? format_number($product->effective_reorder_point) : '—' }}</div>
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
                                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                        <h2>Summary</h2>
                                        <span class="rule"></span>
                                    </div>
                                    <div class="vlist" style="margin-top:12px">
                                        <div class="srow"><span class="l">On Hand</span><span class="v">{{ format_number($stockQty) }}</span></div>
                                        <div class="srow"><span class="l">Sales Price</span><span class="v">{{ format_number($product->sales_price ?? 0) }}</span></div>
                                        <div class="srow"><span class="l">Purchase Price</span><span class="v">{{ format_number($product->purchase_price ?? 0) }}</span></div>
                                    </div>
                                    <div class="gt"><span class="l">Tax Rate</span><span class="v">{{ format_number($product->tax_rate ?? 0) }}%</span></div>
                                </div>

                                <div class="rail-sec">
                                    <div class="sec-head">
                                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                                        <h2>Quick Nav</h2>
                                        <span class="rule"></span>
                                    </div>
                                    <div class="vlist">
                                        <a href="{{ route('accounting.stock-adjustments.create', ['product_id' => $product->id]) }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg></span>
                                            Adjust Stock
                                        </a>
                                        <a href="{{ route('accounting.stock-transfers.create', ['product_id' => $product->id]) }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg></span>
                                            Transfer Stock
                                        </a>
                                        <a href="{{ route('accounting.reports.stock-movement', ['product_id' => $product->id]) }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg></span>
                                            Stock Movement
                                        </a>
                                        <a href="{{ route('accounting.inventory-valuation.index') }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                                            Inventory Valuation
                                        </a>
                                        <button type="button" class="vitem" onclick="window.print()">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg></span>
                                            Print
                                        </button>
                                        <a href="{{ route('accounting.products.index') }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></span>
                                            All Products
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
