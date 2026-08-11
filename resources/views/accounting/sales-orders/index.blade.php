<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $fboxIcons = [
            'total' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
            'draft' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z',
            'sent' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
            'confirmed' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'fulfilled' => 'M5 13l4 4L19 7',
            'cancelled' => 'M6 18L18 6M6 6l12 12',
            'void' => 'M6 18L18 6M6 6l12 12',
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 'q2-fbox-ic--ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 'q2-fbox-ic--steel', 'val' => number_format($stats['draft']->total ?? 0)],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'sent', 'cls' => 'q2-fbox-ic--teal', 'val' => number_format($stats['sent']->total ?? 0)],
            ['key' => 'confirmed', 'label' => __('Confirmed'), 'icon' => 'confirmed', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['confirmed']->total ?? 0)],
            ['key' => 'fulfilled', 'label' => __('Fulfilled'), 'icon' => 'fulfilled', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['fulfilled']->total ?? 0)],
            ['key' => 'cancelled', 'label' => __('Cancelled'), 'icon' => 'cancelled', 'cls' => 'q2-fbox-ic--red', 'val' => number_format($stats['cancelled']->total ?? 0)],
            ['key' => 'void', 'label' => __('Void'), 'icon' => 'void', 'cls' => 'q2-fbox-ic--gray', 'val' => number_format($stats['void']->total ?? 0)],
        ];
        $sortOptions = [
            'date-desc' => __('Newest first'),
            'date-asc' => __('Oldest first'),
            'amount-desc' => __('Total: high → low'),
            'amount-asc' => __('Total: low → high'),
            'status' => __('Status'),
        ];
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §1 head --}}
            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Sales Orders') }}</h1>
                    <p class="q2-sub">{{ __('Track, confirm and fulfil customer sales orders.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.sales-orders.export', request()->query()) }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Export') }}</a>
                    <a href="{{ route('accounting.sales-orders.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Create Sales Order') }}</a>
                </div>
            </div>

            {{-- §1 toolbar --}}
            <div class="q2-toolbar">
                <div class="scoped-search-field" style="max-width: 26.25rem">
                    <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer, number, reference…" autocomplete="off"
                           form="so-list-form" oninput="debounceSoSearch(this)" />
                    <span class="scoped-search-divider" aria-hidden="true"></span>
                    <button type="button" class="scoped-search-open" title="{{ __('Search this list') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: 'sales-order' } }))">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <select name="sort" form="so-list-form" class="q2-select q2-select--sm" style="width: 13rem" onchange="this.form.submit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <form id="so-list-form" method="GET" action="{{ route('accounting.sales-orders.index') }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}" />
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="sort" value="{{ $sort }}" />
            </form>

            {{-- §1 status filter boxes --}}
            <div class="q2-fbox-grid">
                @foreach($fboxes as $box)
                    <a href="{{ route('accounting.sales-orders.index', $box['key'] ? ['status' => $box['key']] : []) }}"
                       class="q2-fbox @if($activeStatus === $box['key']) is-active @endif">
                        <span class="q2-fbox-top">
                            <span class="q2-fbox-ic {{ $box['cls'] }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $fboxIcons[$box['icon']] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span class="q2-fbox-lbl">{{ $box['label'] }}</span>
                        </span>
                        <span class="q2-fbox-val">{{ $box['val'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- §1 shell: main + rail --}}
            <div class="q2-shell">
                <div class="q2-main">
                    <div class="q2-card q2-card--list">
                        <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                            <table class="q2-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:14%">{{ __('Order №') }}</th>
                                        <th style="width:24%">{{ __('Customer') }}</th>
                                        <th style="width:12%">{{ __('Order Date') }}</th>
                                        <th style="width:13%">{{ __('Delivery') }}</th>
                                        <th style="width:13%" class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                                        <th style="width:13%">{{ __('Status') }}</th>
                                        <th style="width:11%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $o)
                                        <tr>
                                            <td class="q2-mono"><a href="{{ route('accounting.sales-orders.show', $o) }}" class="q2-link">{{ $o->sales_order_number }}</a></td>
                                            <td class="q2-amt" style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $o->customer->name ?? '—' }}</td>
                                            <td>{{ $o->order_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $o->expected_delivery_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($o->total) }}</td>
                                            <td>
                                                @switch($o->status)
                                                    @case('draft') <span class="q2-badge q2-badge--draft"><span class="q2-dot"></span>{{ __('Draft') }}</span> @break
                                                    @case('sent') <span class="q2-badge q2-badge--sent"><span class="q2-dot"></span>{{ __('Sent') }}</span> @break
                                                    @case('confirmed') <span class="q2-badge q2-badge--confirmed"><span class="q2-dot"></span>{{ __('Confirmed') }}</span> @break
                                                    @case('fulfilled') <span class="q2-badge q2-badge--fulfilled"><span class="q2-dot"></span>{{ __('Fulfilled') }}</span> @break
                                                    @case('cancelled') <span class="q2-badge q2-badge--cancelled"><span class="q2-dot"></span>{{ __('Cancelled') }}</span> @break
                                                    @case('void') <span class="q2-badge q2-badge--void"><span class="q2-dot"></span>{{ __('Void') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="flex gap-1 justify-end">
                                                    <a href="{{ route('accounting.sales-orders.show', $o) }}" class="q2-ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    @if($o->status === 'draft')
                                                        <a href="{{ route('accounting.sales-orders.edit', $o) }}" class="q2-ibtn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </a>
                                                        <form method="POST" action="{{ route('accounting.sales-orders.send', $o) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="q2-ibtn" title="{{ __('Send') }}" aria-label="{{ __('Send') }}">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if(in_array($o->status, ['draft', 'sent', 'confirmed']))
                                                        @can('sales-orders.void')
                                                            <form method="POST" action="{{ route('accounting.sales-orders.void', $o) }}" class="inline">
                                                                @csrf
                                                                <button type="submit" class="q2-ibtn q2-ibtn--del" title="{{ __('Void') }}" aria-label="{{ __('Void') }}" onclick="return fbConfirmButton(event, 'Void this sales order?', { type: 'danger' })">
                                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l1.5 13a1 1 0 001 .9h7a1 1 0 001-.9L18 6M4 6h16M9 6l.5-2h5l.5 2M10 10v6M14 10v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="q2-empty">{{ __('No sales orders found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                            <div class="q2-pag">
                                <span class="q2-pag-info">{{ __('Showing') }} {{ $orders->firstItem() }}–{{ $orders->lastItem() }} {{ __('of') }} {{ $orders->total() }}</span>
                                <div class="q2-pag-nav">
                                    <a href="{{ $orders->appends(request()->query())->previousPageUrl() }}" class="q2-pag-btn @if($orders->onFirstPage()) is-disabled @endif" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($orders->appends(request()->query())->getUrlRange(1, $orders->lastPage()) as $page => $url)
                                        @if ($page == $orders->currentPage())
                                            <span class="q2-pag-btn is-current">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="q2-pag-btn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $orders->appends(request()->query())->nextPageUrl() }}" class="q2-pag-btn @if(!$orders->hasMorePages()) is-disabled @endif" aria-label="{{ __('Next') }}">›</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- §1 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Views') }}</div>
                        <a href="{{ route('accounting.sales-orders.index') }}" class="q2-vitem @if(!$activeStatus) is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('All Orders') }}
                        </a>
                        <a href="{{ route('accounting.sales-orders.index', ['status' => 'open']) }}" class="q2-vitem @if($activeStatus === 'open') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                            {{ __('Open (Draft + Sent)') }}
                        </a>
                        <a href="{{ route('accounting.sales-orders.index', ['status' => 'confirmed']) }}" class="q2-vitem @if($activeStatus === 'confirmed') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Confirmed') }}
                        </a>
                        <a href="{{ route('accounting.sales-orders.index', ['status' => 'fulfilled']) }}" class="q2-vitem @if($activeStatus === 'fulfilled') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Fulfilled') }}
                        </a>
                        <div class="q2-rule" style="margin:.5rem 0"></div>
                        <div class="q2-rail-group">{{ __('Documents') }}</div>
                        <a href="{{ route('accounting.invoices.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Invoices') }}
                        </a>
                        <a href="{{ route('accounting.sales-receipts.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11h18M3 11l4 8a2 2 0 0 0 1.8 1h6.4a2 2 0 0 0 1.8-1l4-8M3 11l3-6h12l3 6M7 5h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Sales Receipts') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        let soSearchTimer = null;
        function debounceSoSearch(input) {
            clearTimeout(soSearchTimer);
            soSearchTimer = setTimeout(() => {
                const form = document.getElementById('so-list-form');
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }
    </script>
</x-app-layout>
