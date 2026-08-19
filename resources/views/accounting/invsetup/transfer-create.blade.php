@php
    $productsJson = Js::from($productsData);
    $branchesJson = Js::from($branchesData);
@endphp
<x-app-layout>
    <div class="tf-wrap py-6" x-data="tfTransfer({ products: {{ $productsJson }}, branches: {{ $branchesJson }}, cs: {{ Js::from($cs) }} })">

        <div class="tf-crumbs">
            <a href="{{ route('accounting.invsetup.transfers') }}">{{ __('Transfers') }}</a>
            <span class="tf-crumb-sep">/</span>
            <span class="tf-crumb-here">{{ __('New Transfer') }}</span>
        </div>

        <div class="tf-page-head">
            <div>
                <h1 class="tf-page-title">{{ __('New Inventory Transfer') }}</h1>
                <div class="tf-page-sub">{{ __('Move stock between warehouses or branches.') }}</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.invsetup.transfers') }}" class="tf-btn tf-btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" form="tf-form" class="tf-btn tf-btn-cta">{{ __('⇄ Transfer Stock') }}</button>
            </div>
        </div>

        <form method="POST" action="{{ route('accounting.invsetup.transfers.store') }}" id="tf-form" novalidate>
            @csrf
            <input type="hidden" name="product_id" :value="selectedProductId">
            <input type="hidden" name="from_branch_id" :value="fromBranchId">
            <input type="hidden" name="to_branch_id" :value="toBranchId">
            <input type="hidden" name="quantity" :value="qty">
            <input type="hidden" name="date" :value="date">
            <input type="hidden" name="memo" :value="memo">

            <div class="tf-work">
                {{-- LEFT COLUMN --}}
                <div>

                    {{-- Transfer Details --}}
                    <div class="tf-card tf-mb">
                        <div class="tf-card-h">
                            <span class="tf-ic tf-ic-brand">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                            </span>
                            <h2>{{ __('Transfer Details') }}</h2>
                        </div>
                        <div class="tf-pad">
                            <div class="tf-g3">
                                <div class="tf-f tf-span2">
                                    <label class="tf-label">{{ __('Product') }} <span class="tf-req">*</span></label>
                                    <select class="tf-in" x-model="selectedProductId" required>
                                        <option value="">{{ __('Select product...') }}</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="tf-hint" x-show="selectedProduct" x-cloak>
                                        {{ __('On hand at source:') }} <b x-text="onHandSource"></b> &middot; {{ __('SKU') }} <span x-text="selectedSku"></span>
                                    </div>
                                </div>
                                <div class="tf-f">
                                    <label class="tf-label">{{ __('Quantity') }} <span class="tf-req">*</span></label>
                                    <input type="number" class="tf-in tf-in-right" x-model.number="qty" step="0.01" min="0.01" placeholder="0.00" required>
                                </div>
                                <div class="tf-f">
                                    <label class="tf-label">{{ __('Date') }} <span class="tf-req">*</span></label>
                                    <input type="date" class="tf-in" x-model="date" required>
                                </div>
                                <div class="tf-f">
                                    <label class="tf-label">{{ __('Unit Cost') }}</label>
                                    <input type="text" class="tf-in tf-in-disabled" :value="unitCostDisplay" disabled>
                                </div>
                                <div class="tf-f">
                                    <label class="tf-label">{{ __('Estimated Value') }}</label>
                                    <input type="text" class="tf-in tf-in-disabled" :value="estValueDisplay" disabled>
                                </div>
                            </div>
                            @error('product_id') <div class="tf-form-err" style="margin-top:8px">{{ $message }}</div> @enderror
                            @error('quantity') <div class="tf-form-err" style="margin-top:8px">{{ $message }}</div> @enderror
                            @error('date') <div class="tf-form-err" style="margin-top:8px">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Branches --}}
                    <div class="tf-card tf-mb">
                        <div class="tf-card-h">
                            <span class="tf-ic tf-ic-deep">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </span>
                            <h2>{{ __('Branches') }}</h2>
                        </div>
                        <div class="tf-pad">
                            <div class="tf-branchrow">
                                <div class="tf-f" style="margin:0">
                                    <label class="tf-label">{{ __('From Branch') }} <span class="tf-req">*</span></label>
                                    <select class="tf-in" x-model="fromBranchId" required>
                                        <option value="">{{ __('Select source...') }}</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="tf-swap" @click="swapBranches()" title="{{ __('Swap branches') }}" aria-label="{{ __('Swap branches') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                                </button>
                                <div class="tf-f" style="margin:0">
                                    <label class="tf-label">{{ __('To Branch') }} <span class="tf-req">*</span></label>
                                    <select class="tf-in" x-model="toBranchId" required>
                                        <option value="">{{ __('Select destination...') }}</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="tf-f" style="margin-top:14px">
                                <label class="tf-label">{{ __('On hand at destination') }}</label>
                                <input type="text" class="tf-in tf-in-disabled" :value="onHandDest" disabled>
                            </div>
                            @error('from_branch_id') <div class="tf-form-err">{{ $message }}</div> @enderror
                            @error('to_branch_id') <div class="tf-form-err">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="tf-card">
                        <div class="tf-card-h">
                            <span class="tf-ic tf-ic-aux">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            </span>
                            <h2>{{ __('Notes') }}</h2>
                        </div>
                        <div class="tf-pad">
                            <div class="tf-f" style="margin:0">
                                <label class="tf-label">{{ __('Memo / Reason') }}</label>
                                <textarea class="tf-in tf-textarea" x-model="memo" placeholder="{{ __('Internal notes about this transfer…') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT SIDEBAR --}}
                <aside>
                    <div class="tf-card">
                        <div class="tf-card-h">
                            <span class="tf-ic tf-ic-brand">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </span>
                            <h2>{{ __('Transfer Summary') }}</h2>
                        </div>
                        <div class="tf-pad">
                            <div class="tf-sumrow">
                                <span class="tf-sumrow-l">{{ __('Product') }}</span>
                                <span class="tf-sumrow-v" x-text="selectedProductName || '—'"></span>
                            </div>
                            <div class="tf-sumrow">
                                <span class="tf-sumrow-l">{{ __('Quantity') }}</span>
                                <span class="tf-sumrow-v tf-money" x-text="qty || '0'"></span>
                            </div>
                            <div class="tf-sumrow">
                                <span class="tf-sumrow-l">{{ __('Route') }}</span>
                                <span class="tf-route">
                                    <span x-text="fromBranchName || '—'"></span>
                                    <span class="tf-route-arr">&rarr;</span>
                                    <span x-text="toBranchName || '—'"></span>
                                </span>
                            </div>
                            <div class="tf-sumrow">
                                <span class="tf-sumrow-l">{{ __('Unit cost') }}</span>
                                <span class="tf-sumrow-v tf-money" x-text="unitCostDisplay"></span>
                            </div>
                            <div class="tf-sumrow">
                                <span class="tf-sumrow-l">{{ __('Estimated value') }}</span>
                                <span class="tf-sumrow-v tf-money" x-text="estValueDisplay"></span>
                            </div>
                            <div class="tf-note-chip">
                                {{ __('Status on submit: In Transit — stock deducts at source, adds on receipt.') }}
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            {{-- STICKY ACTION BAR --}}
            <div class="tf-actionbar tf-card">
                <a href="{{ route('accounting.invsetup.transfers') }}" class="tf-btn tf-btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="tf-btn tf-btn-cta">{{ __('⇄ Transfer Stock') }}</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tfTransfer', (cfg) => ({
            products: cfg.products,
            branches: cfg.branches,
            cs: cfg.cs,
            selectedProductId: @js(old('product_id')),
            fromBranchId: @js(old('from_branch_id')),
            toBranchId: @js(old('to_branch_id')),
            qty: @js(old('quantity', '')),
            date: @js(old('date', date('Y-m-d'))),
            memo: @js(old('memo')),

            get selectedProduct() {
                return this.products.find(p => p.id == this.selectedProductId) || null;
            },
            get selectedProductName() {
                return this.selectedProduct?.name || '';
            },
            get selectedSku() {
                return this.selectedProduct?.sku || '';
            },
            get unitCost() {
                return this.selectedProduct?.unit_cost || 0;
            },
            get unitCostDisplay() {
                return this.cs + this.formatNum(this.unitCost);
            },
            get onHandSource() {
                if (!this.selectedProduct || !this.fromBranchId) return '0';
                return this.formatNum(this.selectedProduct.on_hand[this.fromBranchId] || 0);
            },
            get onHandDest() {
                if (!this.selectedProduct || !this.toBranchId) return '0';
                return this.formatNum(this.selectedProduct.on_hand[this.toBranchId] || 0);
            },
            get estValue() {
                return (parseFloat(this.qty) || 0) * this.unitCost;
            },
            get estValueDisplay() {
                return this.cs + this.formatNum(this.estValue);
            },
            get fromBranchName() {
                return this.branches.find(b => b.id == this.fromBranchId)?.name || '';
            },
            get toBranchName() {
                return this.branches.find(b => b.id == this.toBranchId)?.name || '';
            },
            swapBranches() {
                const tmp = this.fromBranchId;
                this.fromBranchId = this.toBranchId;
                this.toBranchId = tmp;
            },
            formatNum(n) {
                return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        }));
    });
    </script>
    @endpush
</x-app-layout>
