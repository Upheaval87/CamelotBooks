<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $fboxIcons = [
            'total' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
            'draft' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z',
            'sent' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
            'partially_paid' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10',
            'paid' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'overdue' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'void' => 'M6 18L18 6M6 6l12 12',
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 'q2-fbox-ic--ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 'q2-fbox-ic--steel', 'val' => number_format($stats['draft']->total ?? 0)],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'sent', 'cls' => 'q2-fbox-ic--teal', 'val' => number_format($stats['sent']->total ?? 0)],
            ['key' => 'partially_paid', 'label' => __('Partially Paid'), 'icon' => 'partially_paid', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['partially_paid']->total ?? 0)],
            ['key' => 'paid', 'label' => __('Paid'), 'icon' => 'paid', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($stats['paid']->total ?? 0)],
            ['key' => 'overdue', 'label' => __('Overdue'), 'icon' => 'overdue', 'cls' => 'q2-fbox-ic--red', 'val' => number_format($stats['overdue']->total ?? 0)],
            ['key' => 'void', 'label' => __('Void'), 'icon' => 'void', 'cls' => 'q2-fbox-ic--gray', 'val' => number_format($stats['void']->total ?? 0)],
        ];
        $sortOptions = [
            'date-desc' => __('Newest first'),
            'date-asc' => __('Oldest first'),
            'amount-desc' => __('Amount: high → low'),
            'amount-asc' => __('Amount: low → high'),
            'status' => __('Status'),
        ];
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §1 head --}}
            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Invoices') }}</h1>
                    <p class="q2-sub">{{ __('Track customer invoices and payments.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.invoices.create') }}" class="q2-btn q2-btn--cta q2-btn--sm">＋ {{ __('Create Invoice') }}</a>
                </div>
            </div>

            {{-- §1 toolbar --}}
            <div class="q2-toolbar">
                <div class="scoped-search-field" style="max-width: 26.25rem">
                    <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer, number, reference…" autocomplete="off"
                           form="inv-list-form" oninput="debounceInvSearch(this)" />
                    <span class="scoped-search-divider" aria-hidden="true"></span>
                    <button type="button" class="scoped-search-open" title="{{ __('Search this list') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: 'invoice' } }))">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <select name="sort" form="inv-list-form" class="q2-select q2-select--sm" style="width: 13rem" onchange="this.form.submit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <form id="inv-list-form" method="GET" action="{{ route('accounting.invoices.index') }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}" />
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="sort" value="{{ $sort }}" />
                <input type="hidden" name="from_date" value="{{ request('from_date') }}" />
                <input type="hidden" name="to_date" value="{{ request('to_date') }}" />
                <input type="hidden" name="customer_id" value="{{ request('customer_id') }}" />
            </form>

            {{-- §1 status filter boxes --}}
            <div class="q2-fbox-grid">
                @foreach($fboxes as $box)
                    <a href="{{ route('accounting.invoices.index', $box['key'] ? ['status' => $box['key']] : []) }}"
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
                                        <th style="width:13%">{{ __('Invoice №') }}</th>
                                        <th style="width:20%">{{ __('Customer') }}</th>
                                        <th style="width:11%">{{ __('Date') }}</th>
                                        <th style="width:11%">{{ __('Due Date') }}</th>
                                        <th style="width:11%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                        <th style="width:10%" class="q2-right">{{ __('Paid') }} ({{ $cs }})</th>
                                        <th style="width:11%" class="q2-right">{{ __('Balance') }} ({{ $cs }})</th>
                                        <th style="width:11%">{{ __('Status') }}</th>
                                        <th style="width:8%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr>
                                            <td class="q2-mono"><a href="{{ route('accounting.invoices.show', $invoice) }}" class="q2-link">{{ $invoice->invoice_number }}</a></td>
                                            <td class="q2-amt" style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $invoice->customer->name ?? '—' }}</td>
                                            <td>{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($invoice->amount) }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($invoice->amount_paid) }}</td>
                                            <td class="q2-right q2-amt">{{ format_number($invoice->balance_due) }}</td>
                                            <td>
                                                @switch($invoice->status)
                                                    @case('draft') <span class="q2-badge q2-badge--draft"><span class="q2-dot"></span>{{ __('Draft') }}</span> @break
                                                    @case('sent') <span class="q2-badge q2-badge--sent"><span class="q2-dot"></span>{{ __('Sent') }}</span> @break
                                                    @case('partially_paid') <span class="q2-badge q2-badge--partially-paid"><span class="q2-dot"></span>{{ __('Partially Paid') }}</span> @break
                                                    @case('paid') <span class="q2-badge q2-badge--paid"><span class="q2-dot"></span>{{ __('Paid') }}</span> @break
                                                    @case('overdue') <span class="q2-badge q2-badge--overdue"><span class="q2-dot"></span>{{ __('Overdue') }}</span> @break
                                                    @case('void') <span class="q2-badge q2-badge--void"><span class="q2-dot"></span>{{ __('Void') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="flex gap-1 justify-end">
                                                    <a href="{{ route('accounting.invoices.show', $invoice) }}" class="q2-ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    @if($invoice->status === 'draft')
                                                        @can('invoices.edit')
                                                        <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="q2-ibtn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </a>
                                                        @endcan
                                                        @can('invoices.post')
                                                        <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="q2-ibtn" title="{{ __('Post') }}" aria-label="{{ __('Post') }}" onclick="return fbConfirmButton(event, 'Post this invoice?', { type: 'action' })">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                        @endcan
                                                    @endif
                                                    @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                                                        @can('invoices.void')
                                                        <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="q2-ibtn q2-ibtn--del" title="{{ __('Void') }}" aria-label="{{ __('Void') }}" onclick="return fbConfirmButton(event, 'Are you sure you want to void this invoice?', { type: 'danger' })">
                                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l1.5 13a1 1 0 001 .9h7a1 1 0 001-.9L18 6M4 6h16M9 6l.5-2h5l.5 2M10 10v6M14 10v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="q2-empty">{{ __('No invoices found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($invoices->hasPages())
                            <div class="q2-pag">
                                <span class="q2-pag-info">{{ __('Showing') }} {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} {{ __('of') }} {{ $invoices->total() }}</span>
                                <div class="q2-pag-nav">
                                    <a href="{{ $invoices->appends(request()->query())->previousPageUrl() }}" class="q2-pag-btn @if($invoices->onFirstPage()) is-disabled @endif" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($invoices->appends(request()->query())->getUrlRange(1, $invoices->lastPage()) as $page => $url)
                                        @if ($page == $invoices->currentPage())
                                            <span class="q2-pag-btn is-current">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="q2-pag-btn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $invoices->appends(request()->query())->nextPageUrl() }}" class="q2-pag-btn @if(!$invoices->hasMorePages()) is-disabled @endif" aria-label="{{ __('Next') }}">›</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- §1 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Views') }}</div>
                        <a href="{{ route('accounting.invoices.index') }}" class="q2-vitem @if(!$activeStatus) is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('All Invoices') }}
                        </a>
                        <a href="{{ route('accounting.invoices.index', ['status' => 'open']) }}" class="q2-vitem @if($activeStatus === 'open') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                            {{ __('Open (Draft + Sent)') }}
                        </a>
                        <a href="{{ route('accounting.invoices.index', ['status' => 'draft']) }}" class="q2-vitem @if($activeStatus === 'draft') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Drafts') }}
                        </a>
                        <a href="{{ route('accounting.invoices.index', ['status' => 'sent']) }}" class="q2-vitem @if($activeStatus === 'sent') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Sent') }}
                        </a>
                        <a href="{{ route('accounting.invoices.index', ['status' => 'overdue']) }}" class="q2-vitem @if($activeStatus === 'overdue') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Overdue') }}
                        </a>
                        <a href="{{ route('accounting.invoices.index', ['status' => 'paid']) }}" class="q2-vitem @if($activeStatus === 'paid') is-active @endif">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Paid') }}
                        </a>
                        <div class="q2-rule" style="margin:.5rem 0"></div>
                        <div class="q2-rail-group">{{ __('Reports') }}</div>
                        <a href="{{ route('accounting.aging.ar-summary') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('AR Aging Summary') }}
                        </a>
                        <a href="{{ route('accounting.reports.sales-by-customer') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Sales by Customer') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        let invSearchTimer = null;
        function debounceInvSearch(input) {
            clearTimeout(invSearchTimer);
            invSearchTimer = setTimeout(() => {
                const form = document.getElementById('inv-list-form');
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }
    </script>
</x-app-layout>
