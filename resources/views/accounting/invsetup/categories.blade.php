<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Item Categories') }}</span>
        </div>
        <div class="inv-head">
            <div>
                <h1>{{ __('Item Categories') }}</h1>
                <div class="inv-sub">{{ __('Organise inventory by category, set defaults, and track hierarchy.') }}</div>
            </div>
            <div style="display:flex;gap:8px">
                <button class="inv-btn inv-btn-ghost" type="button" onclick="window.print()">{{ __('Export CSV') }}</button>
                <a href="#" class="inv-btn inv-btn-cta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('New Category') }}
                </a>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'categories'])

        <div class="inv-card">
            <div class="inv-card-h">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                </div>
                {{ __('Category Hierarchy') }}
            </div>
            <div class="inv-card-body">
                @forelse($categories as $category)
                <div class="inv-cat-row" style="padding:14px 20px;border-bottom:1px solid var(--line)">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="inv-tree"><button class="inv-caret" type="button">&#9654;</button></div>
                            <div class="inv-tree"><span class="inv-color-dot" style="background:{{ in_array($category->name, ['Electronics', 'Accessories', 'Furniture & Fixtures', 'Office Supplies', 'Digital Goods']) ? '#128f8e' : (in_array($category->name, ['Services', 'Consulting']) ? '#b45309' : '#5f7476') }}"></span></div>
                            <div>
                                <div style="font-size:13px;font-weight:700;color:var(--ink)">{{ $category->name }}</div>
                                @if($category->description)
                                    <div style="font-size:12px;color:var(--faint);margin-top:2px">{{ $category->description }}</div>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span class="inv-chip">{{ $category->products_count }} {{ __('products') }}</span>
                            <button class="inv-ibtn" title="{{ __('Edit') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button class="inv-ibtn del" title="{{ __('Delete') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </div>
                    </div>
                    @if($category->products_count > 0)
                    <div style="margin-left:54px;margin-top:10px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                        <div>
                            <div class="inv-field-label">{{ __('Inventory Asset Account') }}</div>
                            <div class="inv-field-value">{{ $category->defaultInventoryAssetAccount?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="inv-field-label">{{ __('Default Income Account') }}</div>
                            <div class="inv-field-value">{{ $category->defaultIncomeAccount?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="inv-field-label">{{ __('Default COGS Account') }}</div>
                            <div class="inv-field-value">{{ $category->defaultCogsAccount?->name ?? '—' }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                @empty
                <div class="inv-empty">
                    <div class="inv-empty-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    </div>
                    <p>{{ __('No Categories Found') }}</p>
                    <div class="inv-empty-sub">{{ __('Organise your inventory into categories for clearer reporting and default account mappings.') }}</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
