<x-app-layout>
    <x-slot name="header">{{ __('Item Categories') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="inv-hdr">
            <div>
                <h1 class="inv-hdr-t">{{ __('Item Categories') }}</h1>
                <p class="inv-hdr-sub">{{ __('Organise inventory by category, set defaults, and track hierarchy.') }}</p>
            </div>
            <div class="inv-hdr-acts">
                <button class="inv-btn inv-btn-ghost" type="button" onclick="window.print()">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ __('Export CSV') }}
                </button>
                <a href="#" class="inv-btn inv-btn-cta">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('New Category') }}
                </a>
            </div>
        </div>

        <div class="inv-tabs">
            <a href="{{ route('accounting.invsetup.categories') }}" class="inv-tab inv-tab-on">{{ __('Item Categories') }}</a>
            <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-tab">{{ __('Assemblies') }}</a>
            <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-tab">{{ __('Transfers & Adjustments') }}</a>
            <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-tab">{{ __('Stock Count') }}</a>
            <a href="{{ route('accounting.invsetup.uom') }}" class="inv-tab">{{ __('UOM & Landed Costs') }}</a>
            <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-tab">{{ __('Valuation') }}</a>
            <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-tab">{{ __('Low Stock') }}</a>
        </div>

        <div class="inv-shell">
            <div class="inv-main">
                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                        <span>{{ __('Category Hierarchy') }}</span>
                    </div>
                    <div class="inv-card-body">
                        @forelse($categories as $category)
                        <div class="inv-cat-row">
                            <div class="inv-cat-top">
                                <div class="inv-cat-info">
                                    <div class="inv-cat-color" style="background: {{ (in_array($category->name, ['Electronics', 'Accessories', 'Furniture & Fixtures', 'Office Supplies', 'Digital Goods']) ? '#128f8e' : (in_array($category->name, ['Services', 'Consulting']) ? '#b45309' : '#5f7476')) }};"></div>
                                    <div>
                                        <div class="inv-cat-name">{{ $category->name }}</div>
                                        @if($category->description)
                                            <div class="inv-cat-desc">{{ $category->description }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="inv-cat-actions">
                                    <span class="inv-chip">{{ $category->products_count }} {{ __('products') }}</span>
                                    <button class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Edit') }}</button>
                                    <button class="inv-btn inv-btn-sm inv-btn-ghost inv-btn-del">{{ __('Delete') }}</button>
                                </div>
                            </div>
                            @if($category->products_count > 0)
                            <div class="inv-cat-detail">
                                <div class="inv-cat-dets">
                                    <div class="inv-inv-det">
                                        <span class="inv-det-lbl">{{ __('Inventory Asset Account') }}</span>
                                        <span class="inv-det-val">{{ $category->defaultInventoryAssetAccount?->name ?? '—' }}</span>
                                    </div>
                                    <div class="inv-inv-det">
                                        <span class="inv-det-lbl">{{ __('Default Income Account') }}</span>
                                        <span class="inv-det-val">{{ $category->defaultIncomeAccount?->name ?? '—' }}</span>
                                    </div>
                                    <div class="inv-inv-det">
                                        <span class="inv-det-lbl">{{ __('Default COGS Account') }}</span>
                                        <span class="inv-det-val">{{ $category->defaultCogsAccount?->name ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="inv-empty">
                            <div class="inv-empty-title">{{ __('No Categories Found') }}</div>
                            <p class="inv-empty-desc">{{ __('Organise your inventory into categories for clearer reporting and default account mappings.') }}</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="inv-rail">
                <div class="inv-rail-card">
                    <div class="inv-rail-sec">
                        <div class="inv-rail-sec-head">
                            <svg class="inv-rail-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <span class="inv-rail-sec-label">{{ __('Quick Nav') }}</span>
                        </div>
                        <div class="inv-rail-rule"></div>
                        <a href="{{ route('accounting.invsetup.categories') }}" class="inv-rail-item inv-rail-item-on">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                            {{ __('Categories') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24"/></svg>
                            {{ __('Assemblies') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                            {{ __('Transfers & Adjustments') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            {{ __('Valuation & Low Stock') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
