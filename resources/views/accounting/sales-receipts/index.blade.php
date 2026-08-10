<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $sort = $sort ?? request('sort', 'date-desc');
        $sortOptions = [
            'date-desc' => __('Sort: Newest first'),
            'date-asc' => __('Sort: Oldest first'),
            'amount-desc' => __('Sort: Total high → low'),
            'amount-asc' => __('Sort: Total low → high'),
            'status' => __('Sort: Status'),
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'tile' => 't-ink',
             'icon' => '<rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 9h18" stroke="currentColor" stroke-width="2"/>',
             'val' => number_format((int) $statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'tile' => 't-teal',
             'icon' => '<path d="M4 20h4L20 8l-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
             'val' => number_format((int) ($stats['draft']->total ?? 0))],
            ['key' => 'posted', 'label' => __('Posted'), 'tile' => 't-mint',
             'icon' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
             'val' => number_format((int) ($stats['posted']->total ?? 0))],
            ['key' => 'voided', 'label' => __('Voided'), 'tile' => 't-gray',
             'icon' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
             'val' => number_format((int) ($stats['voided']->total ?? 0))],
        ];
    @endphp

    <div class="sr-suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §1 page head --}}
            <div class="page-head">
                <div>
                    <h1>{{ __('Sales Receipts') }}</h1>
                    <div class="sub">{{ __('Record customer payments and receipts.') }}</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.sales-receipts.export', request()->query()) }}" class="btn btn-ghost btn-sm">⇩ {{ __('Export') }}</a>
                    <a href="{{ route('accounting.sales-receipts.create') }}" class="btn btn-cta">＋ {{ __('Create Sales Receipt') }}</a>
                </div>
            </div>

            <div class="shell">
                <section class="card">
                    <div class="card-sec">
                        {{-- §1 stat boxes --}}
                        <div class="statgrid">
                            @foreach($fboxes as $box)
                                <a href="{{ route('accounting.sales-receipts.index', $box['key'] ? ['status' => $box['key']] : []) }}"
                                   class="fbox @if($activeStatus === $box['key']) on @endif">
                                    <span class="t {{ $box['tile'] }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $box['icon'] !!}</svg></span>
                                    <span><span class="l">{{ $box['label'] }}</span><span class="v" style="display:block">{{ $box['val'] }}</span></span>
                                </a>
                            @endforeach
                        </div>

                        {{-- §1 controls: search + sort --}}
                        <div class="controls">
                            <div class="search">
                                <div class="scoped-search-field">
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
                            </div>
                            <select name="sort" form="sr-list-form" class="input" style="width:13rem" onchange="this.form.submit()">
                                @foreach($sortOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <form id="sr-list-form" method="GET" action="{{ route('accounting.sales-receipts.index') }}">
                        <input type="hidden" name="status" value="{{ $activeStatus }}" />
                        <input type="hidden" name="search" value="{{ request('search') }}" />
                        <input type="hidden" name="sort" value="{{ $sort }}" />
                    </form>

                    {{-- §1 list --}}
                    <div class="card-sec" style="padding-top:6px">
                        <div class="li-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:16%">{{ __('Receipt #') }}</th>
                                        <th style="width:24%">{{ __('Customer') }}</th>
                                        <th style="width:12%">{{ __('Date') }}</th>
                                        <th style="width:14%">{{ __('Method') }}</th>
                                        <th class="num" style="width:12%">{{ __('Total') }} ({{ $cs }})</th>
                                        <th style="width:10%">{{ __('Status') }}</th>
                                        <th style="width:12%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($receipts as $receipt)
                                        <tr>
                                            <td class="mono"><a href="{{ route('accounting.sales-receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></td>
                                            <td class="em">{{ $receipt->customer->name ?? __('Walk-in Customer') }}</td>
                                            <td class="em">{{ $receipt->receipt_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="em">{{ $receipt->payments->first()?->paymentMethod?->name ?? '—' }}</td>
                                            <td class="numr">{{ format_number($receipt->total) }}</td>
                                            <td>
                                                @switch($receipt->status)
                                                    @case('draft') <span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span> @break
                                                    @case('posted') <span class="badge b-post"><span class="bdot"></span>{{ __('Posted') }}</span> @break
                                                    @case('voided') <span class="badge b-void"><span class="bdot"></span>{{ __('Voided') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.sales-receipts.show', $receipt) }}" class="ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                                    </a>
                                                    @if($receipt->status === 'draft')
                                                        @can('sales-receipts.edit')
                                                            <a href="{{ route('accounting.sales-receipts.edit', $receipt) }}" class="ibtn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4L20 8l-4-4L4 16v4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                                            </a>
                                                        @endcan
                                                        @can('sales-receipts.post')
                                                            <a href="{{ route('accounting.sales-receipts.post-page', $receipt) }}" class="ibtn" title="{{ __('Post') }}" aria-label="{{ __('Post') }}">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 17V7l14 5-14 5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                                            </a>
                                                        @endcan
                                                    @endif
                                                    @if($receipt->status === 'posted')
                                                        @can('sales-receipts.void')
                                                            <form method="POST" action="{{ route('accounting.sales-receipts.void', $receipt) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Void this receipt?') }}', { type: 'danger' })">
                                                                @csrf
                                                                <input type="hidden" name="void_reason" value="Voided from receipt list" />
                                                                <button type="submit" class="ibtn del" title="{{ __('Void') }}" aria-label="{{ __('Void') }}">
                                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="empty">{{ __('No sales receipts found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- §1 pager --}}
                    @if($receipts->hasPages())
                        <div class="pagi">
                            <span class="t">{{ __('Showing') }} {{ $receipts->firstItem() }}–{{ $receipts->lastItem() }} {{ __('of') }} {{ $receipts->total() }} {{ __('receipts') }}</span>
                            <div style="display:flex;gap:8px">
                                <a href="{{ $receipts->appends(request()->query())->previousPageUrl() }}" class="btn btn-ghost btn-sm @if($receipts->onFirstPage())" style="opacity:.45;pointer-events:none" aria-disabled="true @endif" aria-label="{{ __('Previous') }}">← {{ __('Prev') }}</a>
                                <a href="{{ $receipts->appends(request()->query())->nextPageUrl() }}" class="btn btn-ghost btn-sm @if(!$receipts->hasMorePages())" style="opacity:.45;pointer-events:none" aria-disabled="true @endif" aria-label="{{ __('Next') }}">{{ __('Next') }} →</a>
                            </div>
                        </div>
                    @endif
                </section>

                {{-- §1 rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><h2>{{ __('Views') }}</h2></div>
                            <div class="vlist">
                                <a href="{{ route('accounting.sales-receipts.index') }}" class="vitem @if(!$activeStatus) on @endif">
                                    <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>{{ __('All Receipts') }}
                                </a>
                                <a href="{{ route('accounting.sales-receipts.index', ['status' => 'draft']) }}" class="vitem @if($activeStatus === 'draft') on @endif">
                                    <span class="ic">✎</span>{{ __('Drafts') }}
                                </a>
                                <a href="{{ route('accounting.sales-receipts.index', ['status' => 'posted']) }}" class="vitem @if($activeStatus === 'posted') on @endif">
                                    <span class="ic">✓</span>{{ __('Posted') }}
                                </a>
                                <a href="{{ route('accounting.sales-receipts.index', ['status' => 'voided']) }}" class="vitem @if($activeStatus === 'voided') on @endif">
                                    <span class="ic">⊘</span>{{ __('Voided') }}
                                </a>
                            </div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 20V11M10.5 20V5M16 20v-7M21 20H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><h2>{{ __('Reports') }}</h2></div>
                            <div class="vlist">
                                <a href="{{ route('accounting.reports.sales-receipts.daily-summary') }}" class="vitem">
                                    <span class="ic">📊</span>{{ __('Daily Summary') }}
                                </a>
                                <a href="{{ route('accounting.reports.sales-receipts.cashbook') }}" class="vitem">
                                    <span class="ic">📒</span>{{ __('Cashbook') }}
                                </a>
                            </div>
                        </div>
                    </section>
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
