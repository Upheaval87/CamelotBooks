<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Inventory Items') }}</h1>
                <div class="inv-sub">{{ $stats['total'] }} {{ __('total items') }} &middot; {{ $stats['active'] }} {{ __('active') }} &middot; {{ $stats['tracked'] }} {{ __('tracked') }}</div>
            </div>
            <a href="{{ route('accounting.inventory.items.create') }}" class="inv-btn inv-btn-cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Item') }}
            </a>
        </div>

        <div class="inv-kpis">
            <div class="inv-kpi"><div class="inv-kpi-l">{{ __('Total') }}</div><div class="inv-kpi-v">{{ $stats['total'] }}</div></div>
            <div class="inv-kpi"><div class="inv-kpi-l">{{ __('Active') }}</div><div class="inv-kpi-v">{{ $stats['active'] }}</div></div>
            <div class="inv-kpi"><div class="inv-kpi-l">{{ __('Tracked') }}</div><div class="inv-kpi-v">{{ $stats['tracked'] }}</div></div>
            <div class="inv-kpi"><div class="inv-kpi-l">{{ __('Low Stock') }}</div><div class="inv-kpi-v" style="color:{{ $stats['low_stock'] > 0 ? 'var(--red-2,#b91c1c)' : 'inherit' }}">{{ $stats['low_stock'] }}</div></div>
        </div>

        <form class="inv-toolbar" method="GET">
            <div class="inv-search inv-grow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" class="inv-input inv-search" placeholder="{{ __('Search by name, SKU, or barcode...') }}" style="width:100%">
            </div>
            <select name="category_id" class="inv-select">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="status" class="inv-select">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
            </select>
            <select name="tracked" class="inv-select">
                <option value="">{{ __('All Tracking') }}</option>
                <option value="yes" {{ request('tracked') === 'yes' ? 'selected' : '' }}>{{ __('Tracked') }}</option>
                <option value="no" {{ request('tracked') === 'no' ? 'selected' : '' }}>{{ __('Not Tracked') }}</option>
            </select>
            <button type="submit" class="inv-btn inv-btn-ghost">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search', 'category_id', 'status', 'tracked']))
            <a href="{{ route('accounting.inventory.items') }}" class="inv-filter-clear">{{ __('Clear') }}</a>
            @endif
        </form>

        @if($products->isEmpty())
        <div class="inv-card inv-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
            <p>{{ __('No items found.') }}</p>
            <div class="inv-empty-sub">{{ __('Try adjusting your filters or create a new item.') }}</div>
        </div>
        @else
        <div class="inv-tbl-wrap">
            <table class="inv-tbl">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Sales Price') }}</th>
                        <th>{{ __('Stock') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($products as $p)
                <tr>
                    <td><a href="{{ route('accounting.inventory.items.show', $p) }}" class="inv-link">{{ $p->name }}</a></td>
                    <td class="inv-mono">{{ $p->sku }}</td>
                    <td>{{ $p->itemCategory?->name ?? '—' }}</td>
                    <td><span class="inv-chip">{{ ucfirst($p->type) }}</span></td>
                    <td class="inv-num">{{ number_format($p->sales_price ?? 0, 2) }}</td>
                    <td class="inv-num">{{ number_format($p->stock_qty ?? 0, 0) }}</td>
                    <td><span class="inv-badge inv-badge-{{ $p->is_active ? 'active' : 'inactive' }}"><span class="inv-badge-dot"></span>{{ $p->is_active ? __('Active') : __('Inactive') }}</span></td>
                    <td>
                        <div style="display:flex;gap:4px;justify-content:flex-end">
                            <a href="{{ route('accounting.inventory.items.show', $p) }}" class="inv-btn inv-btn-sm inv-btn-ghost" title="{{ __('View') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="{{ route('accounting.inventory.items.edit', $p) }}" class="inv-btn inv-btn-sm inv-btn-ghost" title="{{ __('Edit') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="inv-pag">
            <div class="inv-pag-info">{{ __('Showing') }} {{ $products->firstItem() }}–{{ $products->lastItem() }} {{ __('of') }} {{ $products->total() }} {{ __('items') }}</div>
            <div class="inv-pag-nav">{{ $products->links() }}</div>
        </div>
        @endif
    </div>
</x-app-layout>
