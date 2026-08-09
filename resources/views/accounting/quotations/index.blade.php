<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $fboxIcons = [
            'total' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
            'draft' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z',
            'sent' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
            'accepted' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'declined' => 'M6 18L18 6M6 6l12 12',
            'converted' => 'M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4',
            'void' => 'M6 18L18 6M6 6l12 12',
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 'q2-fbox-ic--ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 'q2-fbox-ic--steel', 'val' => number_format($stats['draft']->total ?? 0)],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'sent', 'cls' => 'q2-fbox-ic--teal', 'val' => number_format($stats['sent']->total ?? 0)],
            ['key' => 'accepted', 'label' => __('Accepted'), 'icon' => 'accepted', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['accepted']->total ?? 0)],
            ['key' => 'declined', 'label' => __('Declined'), 'icon' => 'declined', 'cls' => 'q2-fbox-ic--red', 'val' => number_format($stats['declined']->total ?? 0)],
            ['key' => 'converted', 'label' => __('Converted'), 'icon' => 'converted', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['converted']->total ?? 0)],
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
                    <h1 class="q2-title">{{ __('Quotations') }}</h1>
                    <p class="q2-sub">{{ __('Track, send and convert customer quotations.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.quotations.export', request()->query()) }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Export') }}</a>
                    <a href="{{ route('accounting.quotations.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Create Quotation') }}</a>
                </div>
            </div>

            {{-- §1 toolbar --}}
            <div class="q2-toolbar">
                <div class="scoped-search-field" style="max-width: 26.25rem">
                    <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer, number, reference…" autocomplete="off"
                           form="quot-list-form" oninput="debounceQuotSearch(this)" />
                    <span class="scoped-search-divider" aria-hidden="true"></span>
                    <button type="button" class="scoped-search-open" title="{{ __('Search this list') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: 'quotation' } }))">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <select name="sort" form="quot-list-form" class="q2-select q2-select--sm" style="width: 13rem" onchange="this.form.submit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <form id="quot-list-form" method="GET" action="{{ route('accounting.quotations.index') }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}" />
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="sort" value="{{ $sort }}" />
            </form>

            {{-- §1 status filter boxes --}}
            <div class="q2-fbox-grid">
                @foreach($fboxes as $box)
                    <a href="{{ route('accounting.quotations.index', $box['key'] ? ['status' => $box['key']] : []) }}"
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
                                        <th style="width:14%">{{ __('Quotation №') }}</th>
                                        <th style="width:24%">{{ __('Customer') }}</th>
                                        <th style="width:12%">{{ __('Date') }}</th>
                                        <th style="width:13%">{{ __('Valid Until') }}</th>
                                        <th style="width:13%" class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                                        <th style="width:13%">{{ __('Status') }}</th>
                                        <th style="width:11%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($quotations as $q)
                                        <tr>
                                            <td class="q2-mono"><a href="{{ route('accounting.quotations.show', $q) }}" class="q2-link">{{ $q->quotation_number }}</a></td>
                                            <td class="q2-amt" style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $q->customer->name ?? '—' }}</td>
                                            <td>{{ $q->quotation_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $q->valid_until?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($q->total) }}</td>
                                            <td>
                                                @switch($q->status)
                                                    @case('draft') <span class="q2-badge q2-badge--draft"><span class="q2-dot"></span>{{ __('Draft') }}</span> @break
                                                    @case('sent') <span class="q2-badge q2-badge--sent"><span class="q2-dot"></span>{{ __('Sent') }}</span> @break
                                                    @case('accepted') <span class="q2-badge q2-badge--accepted"><span class="q2-dot"></span>{{ __('Accepted') }}</span> @break
                                                    @case('declined') <span class="q2-badge q2-badge--declined"><span class="q2-dot"></span>{{ __('Declined') }}</span> @break
                                                    @case('converted') <span class="q2-badge q2-badge--converted"><span class="q2-dot"></span>{{ __('Converted') }}</span> @break
                                                    @case('void') <span class="q2-badge q2-badge--void"><span class="q2-dot"></span>{{ __('Void') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="flex gap-1 justify-end">
                                                    <a href="{{ route('accounting.quotations.show', $q) }}" class="q2-ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    @if($q->status === 'draft')
                                                        <a href="{{ route('accounting.quotations.edit', $q) }}" class="q2-ibtn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </a>
                                                        <form method="POST" action="{{ route('accounting.quotations.send', $q) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="q2-ibtn" title="{{ __('Send') }}" aria-label="{{ __('Send') }}">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if(in_array($q->status, ['draft', 'sent', 'accepted']))
                                                        @can('quotations.void')
                                                            <form method="POST" action="{{ route('accounting.quotations.void', $q) }}" class="inline">
                                                                @csrf
                                                                <button type="submit" class="q2-ibtn q2-ibtn--del" title="{{ __('Void') }}" aria-label="{{ __('Void') }}" onclick="return fbConfirmButton(event, 'Void this quotation?', { type: 'danger' })">
                                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l1.5 13a1 1 0 001 .9h7a1 1 0 001-.9L18 6M4 6h16M9 6l.5-2h5l.5 2M10 10v6M14 10v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="q2-empty">{{ __('No quotations found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($quotations->hasPages())
                            <div class="q2-pag">
                                <span class="q2-pag-info">{{ __('Showing') }} {{ $quotations->firstItem() }}–{{ $quotations->lastItem() }} {{ __('of') }} {{ $quotations->total() }}</span>
                                <div class="q2-pag-nav">
                                    <a href="{{ $quotations->appends(request()->query())->previousPageUrl() }}" class="q2-pag-btn @if($quotations->onFirstPage()) is-disabled @endif" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($quotations->appends(request()->query())->getUrlRange(1, $quotations->lastPage()) as $page => $url)
                                        @if ($page == $quotations->currentPage())
                                            <span class="q2-pag-btn is-current">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="q2-pag-btn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $quotations->appends(request()->query())->nextPageUrl() }}" class="q2-pag-btn @if(!$quotations->hasMorePages()) is-disabled @endif" aria-label="{{ __('Next') }}">›</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- §1 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Views') }}</div>
                        <a href="{{ route('accounting.quotations.index') }}" class="q2-vitem @if(!$activeStatus) is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('All Quotations') }}
                        </a>
                        <a href="{{ route('accounting.quotations.index', ['status' => 'open']) }}" class="q2-vitem @if($activeStatus === 'open') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                            {{ __('Open (Draft + Sent)') }}
                        </a>
                        <a href="{{ route('accounting.quotations.index', ['status' => 'accepted']) }}" class="q2-vitem @if($activeStatus === 'accepted') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Accepted') }}
                        </a>
                        <a href="{{ route('accounting.quotations.index', ['status' => 'converted']) }}" class="q2-vitem @if($activeStatus === 'converted') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Converted') }}
                        </a>
                        <div class="q2-rule" style="margin:.5rem 0"></div>
                        <div class="q2-rail-group">{{ __('Reports') }}</div>
                        <a href="{{ route('accounting.reports.quotation-register') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 17h6M9 13h6M9 9h4M5 3h14a2 2 0 012 2v16H3V5a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Quotation Register') }}
                        </a>
                        <a href="{{ route('accounting.reports.sales-pipeline') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Sales Pipeline') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        let quotSearchTimer = null;
        function debounceQuotSearch(input) {
            clearTimeout(quotSearchTimer);
            quotSearchTimer = setTimeout(() => {
                const form = document.getElementById('quot-list-form');
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }
    </script>
</x-app-layout>
