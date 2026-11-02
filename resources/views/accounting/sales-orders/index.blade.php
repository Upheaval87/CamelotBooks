<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $sort = $sort ?? 'date-desc';
        $sortOptions = [
            'date-desc' => __('Newest first'),
            'date-asc' => __('Oldest first'),
            'amount-desc' => __('Total: high → low'),
            'amount-asc' => __('Total: low → high'),
            'status' => __('Status'),
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z', 'cls' => 'ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z', 'cls' => 'steel', 'val' => number_format($stats['draft']->total ?? 0)],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z', 'cls' => 'teal', 'val' => number_format($stats['sent']->total ?? 0)],
            ['key' => 'confirmed', 'label' => __('Confirmed'), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'cls' => 'mint', 'val' => number_format($stats['confirmed']->total ?? 0)],
            ['key' => 'fulfilled', 'label' => __('Fulfilled'), 'icon' => 'M5 13l4 4L19 7', 'cls' => 'mint', 'val' => number_format($stats['fulfilled']->total ?? 0)],
            ['key' => 'cancelled', 'label' => __('Cancelled'), 'icon' => 'M6 18L18 6M6 6l12 12', 'cls' => 'red', 'val' => number_format($stats['cancelled']->total ?? 0)],
            ['key' => 'void', 'label' => __('Void'), 'icon' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z', 'cls' => 'gray', 'val' => number_format($stats['void']->total ?? 0)],
        ];
        $today = now()->startOfDay();
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="so2">

                {{-- page head --}}
                <div class="page-head">
                    <div>
                        <h1>{{ __('Sales Orders') }}</h1>
                        <p class="sub">{{ __('Track, confirm and fulfil customer sales orders.') }}</p>
                    </div>
                    <div class="page-actions">
                        <a href="{{ route('accounting.sales-orders.export', request()->query()) }}" class="btn btn-ghost btn-sm">{{ __('Export') }}</a>
                        <a href="{{ route('accounting.sales-orders.create') }}" class="btn btn-cta btn-sm">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                            {{ __('Create Sales Order') }}
                        </a>
                    </div>
                </div>

                {{-- toolbar --}}
                <div class="tb">
                    <div class="search">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round"/></svg>
                        <input type="text" id="soSearch" value="{{ request('search') }}" placeholder="{{ __('Customer, number, reference…') }}" autocomplete="off" oninput="debounceSoSearch(this)" />
                    </div>
                    <div class="sep"></div>
                    <select name="sort" onchange="soListForm().submit()">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="count">{{ __('Showing') }} {{ $orders->firstItem() }}–{{ $orders->lastItem() }} {{ __('of') }} {{ $orders->total() }}</span>
                </div>

                {{-- status tiles --}}
                <div class="fboxes">
                    @foreach($fboxes as $box)
                        <a href="{{ route('accounting.sales-orders.index', $box['key'] ? ['status' => $box['key']] : []) }}"
                           class="fbox @if($activeStatus === $box['key'] && ! ($box['key'] === '' && request()->hasAny(['search','sort']))) on @endif">
                            <span class="fi {{ $box['cls'] }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $box['icon'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span class="fm">
                                <b>{{ $box['val'] }}</b>
                                <span>{{ $box['label'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <form id="so-list-form" method="GET" action="{{ route('accounting.sales-orders.index') }}">
                    <input type="hidden" name="status" value="{{ $activeStatus }}" />
                    <input type="hidden" name="search" value="{{ request('search') }}" />
                    <input type="hidden" name="sort" value="{{ $sort }}" />
                </form>

                {{-- shell: table + rail --}}
                <div class="shell">
                    <div class="card">
                        @forelse($orders as $o)
                            @php
                                $oQtyOrdered = (float) $o->lines->sum('quantity');
                                $oQtyFulfilled = (float) $o->lines->sum('qty_fulfilled');
                                $oPct = $oQtyOrdered > 0 ? round(($oQtyFulfilled / $oQtyOrdered) * 100) : 0;
                                $oDelivered = $o->delivered_on?->copy()->startOfDay();
                                if ($o->status === 'fulfilled' || $oDelivered) { $oDue = ['cls' => 'good', 'txt' => __('Delivered') . ' ' . ($o->delivered_on?->format('M d') ?? '')]; }
                                elseif ($o->status === 'confirmed' && $o->expected_delivery_date) {
                                    $days = (int) $today->diffInDays($o->expected_delivery_date->copy()->startOfDay(), false);
                                    $oDue = $days < 0 ? ['cls' => 'exp', 'txt' => __('Overdue') . ' ' . abs($days) . 'd'] :
                                            ($days <= 3 ? ['cls' => 'soon', 'txt' => __('Due in') . ' ' . $days . 'd'] : ['cls' => 'ok', 'txt' => __('Due') . ' ' . $o->expected_delivery_date->format('M d')]);
                                } elseif (in_array($o->status, ['confirmed', 'sent'])) { $oDue = ['cls' => 'ok', 'txt' => __('Due') . ' ' . ($o->expected_delivery_date?->format('M d') ?? '—')]; }
                                else { $oDue = null; }
                            @endphp
                            <table class="tbl">
                                <tbody>
                                    <tr class="row">
                                        <td width="14%">
                                            <a class="ref" href="{{ route('accounting.sales-orders.show', $o) }}">{{ $o->sales_order_number }}</a>
                                        </td>
                                        <td width="24%">
                                            <span class="ocust">{{ $o->customer->name ?? '—' }}</span>
                                            @if($o->customer?->phone ?? $o->phone)
                                                <span class="osub">{{ $o->phone ?? $o->customer->phone }}</span>
                                            @endif
                                        </td>
                                        <td width="18%">
                                            <div class="due">
                                                <span class="d2">{{ $o->expected_delivery_date?->format('M d, Y') ?? '—' }}</span>
                                                @if($oDue)
                                                    <span class="xc {{ $oDue['cls'] }}">{{ $oDue['txt'] }}</span>
                                                @endif
                                            </div>
                                            @if(in_array($o->status, ['confirmed', 'fulfilled']) && $oQtyOrdered > 0)
                                                <span class="obar" title="{{ $oQtyFulfilled }} / {{ $oQtyOrdered }} {{ __('fulfilled') }}"><i style="width:{{ $oPct }}%" class="{{ $oPct >= 100 ? 'done' : '' }}"></i></span>
                                            @endif
                                        </td>
                                        <td width="13%">{{ $o->order_date?->format('M d, Y') ?? '—' }}</td>
                                        <td width="12%" class="r num">{{ $cs }}{{ format_number($o->total) }}</td>
                                        <td width="9%">
                                            <span class="pill {{ $o->statusClass() }}"><i></i>{{ $o->statusLabel() }}</span>
                                        </td>
                                        <td width="10%">
                                            <div class="oact">
                                                <button type="button" class="ib" title="{{ __('View') }}" aria-label="{{ __('View') }}"
                                                        data-view-url="{{ route('accounting.sales-orders.show', $o) }}?modal=1">
                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </button>
                                                @if($o->status === 'draft')
                                                    <a href="{{ route('accounting.sales-orders.edit', $o) }}" class="ib" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                @endif
                                                @if($o->status === 'confirmed')
                                                    <a href="{{ route('accounting.sales-orders.show', $o) }}?fulfil=1" class="ib" title="{{ __('Fulfil delivery') }}" aria-label="{{ __('Fulfil delivery') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                @endif
                                                @if(in_array($o->status, ['draft', 'sent', 'confirmed']))
                                                    @can('sales-orders.void')
                                                        <form method="POST" action="{{ route('accounting.sales-orders.void', $o) }}" class="i">
                                                            @csrf
                                                            <button type="submit" class="ib delb" title="{{ __('Void') }}" aria-label="{{ __('Void') }}"
                                                                    onclick="return fbConfirmButton(event, '{{ __('Void this sales order?') }}', { type: 'danger' })">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l1.5 13a1 1 0 001 .9h7a1 1 0 001-.9L18 6M4 6h16M9 6l.5-2h5l.5 2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        @empty
                            <div class="empty"><b>{{ __('No sales orders found') }}</b>{{ __('Try adjusting your filters.') }}</div>
                        @endforelse

                        @if($orders->hasPages())
                            <div class="pag">
                                <span class="info">{{ __('Showing') }} {{ $orders->firstItem() }}–{{ $orders->lastItem() }} {{ __('of') }} {{ $orders->total() }}</span>
                                <div class="pages">
                                    <a href="{{ $orders->appends(request()->query())->previousPageUrl() }}" class="@if($orders->onFirstPage()) @endif" style="{{ $orders->onFirstPage() ? 'pointer-events:none;opacity:.5' : '' }}" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($orders->appends(request()->query())->getUrlRange(1, $orders->lastPage()) as $page => $url)
                                        @if ($page == $orders->currentPage())
                                            <span class="on">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $orders->appends(request()->query())->nextPageUrl() }}" class="@if(!$orders->hasMorePages()) @endif" style="{{ !$orders->hasMorePages() ? 'pointer-events:none;opacity:.5' : '' }}" aria-label="{{ __('Next') }}">›</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- rail --}}
                    <aside class="rail">
                        <div class="rail-card">
                            <div class="rail-group">{{ __('Views') }}</div>
                            <a href="{{ route('accounting.sales-orders.index') }}" class="vitem @if(! $activeStatus) on @endif">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke-width="2" stroke-linecap="round"/></svg></span>
                                {{ __('All Orders') }}
                                <span class="cnt">{{ number_format($viewCounts['all'] ?? 0) }}</span>
                            </a>
                            <a href="{{ route('accounting.sales-orders.index', ['status' => 'open']) }}" class="vitem @if($activeStatus === 'open') on @endif">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg></span>
                                {{ __('Open (Draft + Sent)') }}
                                <span class="cnt">{{ number_format($viewCounts['open'] ?? 0) }}</span>
                            </a>
                            <a href="{{ route('accounting.sales-orders.index', ['status' => 'unfulfilled']) }}" class="vitem @if($activeStatus === 'unfulfilled') on @endif">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                {{ __('Unfulfilled') }}
                                <span class="cnt">{{ number_format($viewCounts['unfulfilled'] ?? 0) }}</span>
                            </a>
                            <a href="{{ route('accounting.sales-orders.index', ['status' => 'fulfilled']) }}" class="vitem @if($activeStatus === 'fulfilled') on @endif">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                {{ __('Fulfilled') }}
                                <span class="cnt">{{ number_format($viewCounts['fulfilled'] ?? 0) }}</span>
                            </a>
                            <a href="{{ route('accounting.sales-orders.index', ['status' => 'attention']) }}" class="vitem @if($activeStatus === 'attention') on @endif">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                {{ __('Needs Attention') }}
                                <span class="cnt">{{ number_format($viewCounts['attention'] ?? 0) }}</span>
                            </a>
                            <div class="rail-rule"></div>
                            <div class="rail-group">{{ __('Documents') }}</div>
                            <a href="{{ route('accounting.invoices.index') }}" class="vitem">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                {{ __('Invoices') }}
                            </a>
                            <a href="{{ route('accounting.sales-receipts.index') }}" class="vitem">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11h18M3 11l4 8a2 2 0 0 0 1.8 1h6.4a2 2 0 0 0 1.8-1l4-8M3 11l3-6h12l3 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                {{ __('Sales Receipts') }}
                            </a>
                            <a href="{{ route('accounting.reports.sales-by-customer') }}" class="vitem">
                                <span class="vi"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 3v18h18M8 17v-4m4 4v-8m4 8V9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                {{ __('Sales by Customer') }}
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    {{-- view-modal host (fetched ?modal=1 partial is injected here) --}}
    <div class="so2" id="so-modal-host"></div>

    <script>
        let soSearchTimer = null;
        function soListForm() { return document.getElementById('so-list-form'); }
        function debounceSoSearch(input) {
            clearTimeout(soSearchTimer);
            soSearchTimer = setTimeout(() => {
                const form = soListForm();
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }

        // open the detail card in the modal host (fetch ?modal=1 partial)
        function soOpenModal(url) {
            fetch(url).then(r => r.text()).then(html => {
                document.getElementById('so-modal-host').innerHTML = html;
                document.getElementById('fvScrim') && document.getElementById('fvScrim').classList.add('on');
            });
        }
        document.addEventListener('click', (e) => {
            const b = e.target.closest && e.target.closest('[data-view-url]');
            if (b) { e.preventDefault(); soOpenModal(b.dataset.viewUrl); return; }
            if (e.target.closest && e.target.closest('[data-close="fvScrim"]')) {
                const s = document.getElementById('fvScrim'); if (s) s.classList.remove('on');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') document.querySelectorAll('.fv-scrim.on').forEach(s => s.classList.remove('on'));
        });

        // fulfil from the popup navigates to the show page, which auto-opens the fulfil modal
        window.addEventListener('so:open-fulfil', function (e) {
            if (e.detail && e.detail.url) { window.location.href = e.detail.url; }
        });
    </script>
</x-app-layout>