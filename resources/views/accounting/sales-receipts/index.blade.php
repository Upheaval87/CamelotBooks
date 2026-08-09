<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $fboxIcons = [
            'total' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
            'draft' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z',
            'posted' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'voided' => 'M6 18L18 6M6 6l12 12',
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 'q2-fbox-ic--ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 'q2-fbox-ic--steel', 'val' => number_format($stats['draft']->total ?? 0)],
            ['key' => 'posted', 'label' => __('Posted'), 'icon' => 'posted', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['posted']->total ?? 0)],
            ['key' => 'voided', 'label' => __('Voided'), 'icon' => 'voided', 'cls' => 'q2-fbox-ic--gray', 'val' => number_format($stats['voided']->total ?? 0)],
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
                    <h1 class="q2-title">{{ __('Sales Receipts') }}</h1>
                    <p class="q2-sub">{{ __('Record customer payments and receipts.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.sales-receipts.export', request()->query()) }}" class="q2-btn q2-btn--ghost q2-btn--sm">⇩ {{ __('Export') }}</a>
                    <a href="{{ route('accounting.sales-receipts.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Create Sales Receipt') }}</a>
                </div>
            </div>

            {{-- §1 toolbar --}}
            <div class="q2-toolbar">
                <div class="scoped-search-field" style="max-width: 26.25rem">
                    <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Customer, number…') }}" autocomplete="off"
                           form="sr-list-form" oninput="debounceSrSearch(this)" />
                    <span class="scoped-search-divider" aria-hidden="true"></span>
                    <button type="button" class="scoped-search-open" title="{{ __('Search this list') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: 'sales-receipt' } }))">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <select name="sort" form="sr-list-form" class="q2-select q2-select--sm" style="width: 13rem" onchange="this.form.submit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <form id="sr-list-form" method="GET" action="{{ route('accounting.sales-receipts.index') }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}" />
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="sort" value="{{ $sort }}" />
            </form>

            {{-- §1 status filter boxes --}}
            <div class="q2-fbox-grid--4">
                @foreach($fboxes as $box)
                    <a href="{{ route('accounting.sales-receipts.index', $box['key'] ? ['status' => $box['key']] : []) }}"
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
                                        <th style="width:14%">{{ __('Receipt №') }}</th>
                                        <th style="width:24%">{{ __('Customer') }}</th>
                                        <th style="width:12%">{{ __('Date') }}</th>
                                        <th style="width:13%">{{ __('Method') }}</th>
                                        <th style="width:12%" class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                                        <th style="width:12%">{{ __('Status') }}</th>
                                        <th style="width:13%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($receipts as $receipt)
                                        <tr>
                                            <td class="q2-mono"><a href="{{ route('accounting.sales-receipts.show', $receipt) }}" class="q2-link">{{ $receipt->receipt_number }}</a></td>
                                            <td style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $receipt->customer->name ?? __('Walk-in') }}</td>
                                            <td>{{ $receipt->receipt_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $receipt->payments->first()?->paymentMethod?->name ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($receipt->total) }}</td>
                                            <td>
                                                @switch($receipt->status)
                                                    @case('draft') <span class="q2-badge q2-badge--draft"><span class="q2-dot"></span>{{ __('Draft') }}</span> @break
                                                    @case('posted') <span class="q2-badge q2-badge--posted"><span class="q2-dot"></span>{{ __('Posted') }}</span> @break
                                                    @case('voided') <span class="q2-badge q2-badge--voided"><span class="q2-dot"></span>{{ __('Voided') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="flex gap-1 justify-end">
                                                    <a href="{{ route('accounting.sales-receipts.show', $receipt) }}" class="q2-ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    @if($receipt->status === 'draft')
                                                        @can('sales-receipts.edit')
                                                            <a href="{{ route('accounting.sales-receipts.edit', $receipt) }}" class="q2-ibtn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </a>
                                                        @endcan
                                                        @can('sales-receipts.post')
                                                            <a href="{{ route('accounting.sales-receipts.post-page', $receipt) }}" class="q2-ibtn" title="{{ __('Post') }}" aria-label="{{ __('Post') }}">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </a>
                                                        @endcan
                                                    @endif
                                                    @if($receipt->status === 'posted')
                                                        @can('sales-receipts.void')
                                                            <form method="POST" action="{{ route('accounting.sales-receipts.void', $receipt) }}" class="inline">
                                                                @csrf
                                                                <button type="submit" class="q2-ibtn q2-ibtn--del" title="{{ __('Void') }}" aria-label="{{ __('Void') }}" onclick="return fbConfirmButton(event, 'Void this receipt?', { type: 'danger' })">
                                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l1.5 13a1 1 0 001 .9h7a1 1 0 001-.9L18 6M4 6h16M9 6l.5-2h5l.5 2M10 10v6M14 10v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="q2-empty">{{ __('No sales receipts found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($receipts->hasPages())
                            <div class="q2-pag">
                                <span class="q2-pag-info">{{ __('Showing') }} {{ $receipts->firstItem() }}–{{ $receipts->lastItem() }} {{ __('of') }} {{ $receipts->total() }}</span>
                                <div class="q2-pag-nav">
                                    <a href="{{ $receipts->appends(request()->query())->previousPageUrl() }}" class="q2-pag-btn @if($receipts->onFirstPage()) is-disabled @endif" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($receipts->appends(request()->query())->getUrlRange(1, $receipts->lastPage()) as $page => $url)
                                        @if ($page == $receipts->currentPage())
                                            <span class="q2-pag-btn is-current">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="q2-pag-btn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $receipts->appends(request()->query())->nextPageUrl() }}" class="q2-pag-btn @if(!$receipts->hasMorePages()) is-disabled @endif" aria-label="{{ __('Next') }}">›</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- §1 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Views') }}</div>
                        <a href="{{ route('accounting.sales-receipts.index') }}" class="q2-vitem @if(!$activeStatus) is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('All Receipts') }}
                        </a>
                        <a href="{{ route('accounting.sales-receipts.index', ['status' => 'draft']) }}" class="q2-vitem @if($activeStatus === 'draft') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Drafts') }}
                        </a>
                        <a href="{{ route('accounting.sales-receipts.index', ['status' => 'posted']) }}" class="q2-vitem @if($activeStatus === 'posted') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Posted') }}
                        </a>
                        <a href="{{ route('accounting.sales-receipts.index', ['status' => 'voided']) }}" class="q2-vitem @if($activeStatus === 'voided') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Voided') }}
                        </a>
                        <div class="q2-rule" style="margin:.5rem 0"></div>
                        <div class="q2-rail-group">{{ __('Reports') }}</div>
                        <a href="{{ route('accounting.reports.sales-receipts.daily-summary') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Daily Summary') }}
                        </a>
                        <a href="{{ route('accounting.reports.sales-receipts.cashbook') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 17h6M9 13h6M9 9h4M5 3h14a2 2 0 012 2v16H3V5a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Cashbook') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        let srSearchTimer = null;
        function debounceSrSearch(input) {
            clearTimeout(srSearchTimer);
            srSearchTimer = setTimeout(() => {
                const form = document.getElementById('sr-list-form');
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }
    </script>
</x-app-layout>
