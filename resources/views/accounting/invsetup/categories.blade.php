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
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button" onclick="window.print()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('Export CSV') }}
                </button>
                <a href="{{ route('accounting.inventory.items.create') }}" class="inv-btn inv-btn-cta inv-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Add Item') }}
                </a>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'categories'])

        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                </div>
                <h2>{{ __('Category Hierarchy') }}</h2>
                <span class="inv-rule"></span>
                <span class="inv-chip" style="margin-left:auto">{{ __('hierarchy') }}</span>
            </div>
            <div class="inv-card-body">
                @forelse($categories as $category)
                <ul class="inv-tree-list">
                    <li>
                        <div class="inv-tree">
                            <button class="inv-caret" type="button">&#9654;</button>
                        </div>
                        <span class="inv-color-dot" style="background:{{ in_array($category->name, ['Electronics', 'Accessories', 'Furniture & Fixtures', 'Office Supplies', 'Digital Goods']) ? '#128f8e' : (in_array($category->name, ['Services', 'Consulting']) ? '#b45309' : '#5f7476') }}"></span>
                        <b>{{ $category->name }}</b>
                        <span class="inv-cnt">{{ $category->products_count }}</span>
                        <div class="inv-tree-actions">
                            <span class="inv-chip">{{ $category->products_count }} {{ __('products') }}</span>
                            <button class="inv-ibtn" title="{{ __('Edit') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button class="inv-ibtn del" title="{{ __('Delete') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </div>
                    </li>
                    @if($category->products_count > 0)
                    <div class="inv-tree-detail">
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
                </ul>
                @empty
                <div class="inv-empty" style="padding:48px 20px">
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
