<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', request('view', ''));
        $fboxIcons = [
            'total' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
            'draft' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z',
            'sent' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
            'accepted' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'declined' => 'M6 18L18 6M6 6l12 12',
            'converted' => 'M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4',
            'void' => 'M6 18L18 6M6 6l12 12',
        ];
        $statCount = fn($k) => ($stats[$k] ?? null)?->total ?? 0;
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 'q2-fbox-ic--ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 'q2-fbox-ic--steel', 'val' => number_format($statCount('draft'))],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'sent', 'cls' => 'q2-fbox-ic--teal', 'val' => number_format($statCount('sent'))],
            ['key' => 'accepted', 'label' => __('Accepted'), 'icon' => 'accepted', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($statCount('accepted'))],
            ['key' => 'declined', 'label' => __('Declined'), 'icon' => 'declined', 'cls' => 'q2-fbox-ic--red', 'val' => number_format($statCount('declined'))],
            ['key' => 'converted', 'label' => __('Converted'), 'icon' => 'converted', 'cls' => 'q2-fbox-ic--mint', 'val' => number_format($statCount('converted'))],
            ['key' => 'void', 'label' => __('Void'), 'icon' => 'void', 'cls' => 'q2-fbox-ic--gray', 'val' => number_format($statCount('void'))],
        ];
        $sortOptions = [
            'date-desc' => __('Newest first'),
            'date-asc' => __('Oldest first'),
            'amount-desc' => __('Total: high → low'),
            'amount-asc' => __('Total: low → high'),
            'expiry-asc' => __('Expiry: soonest'),
            'status' => __('Status'),
        ];
        $statusLabel = fn($s) => ucfirst($s);
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

            {{-- §2.1 KPI tiles --}}
            <div class="q2-statgrid q2-statgrid--4" style="margin-bottom:1.125rem">
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none"><path d="M3 3v18h18M7 14l3-3 3 3 4-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Open pipeline') }}</span>
                        <span class="q2-stat-val">{{ $cs }} {{ format_number($kpis['open_pipeline']) }}</span>
                        <span class="q2-stat-var">{{ $kpis['open_count'] }} {{ __('open quotes (draft + sent)') }}</span>
                    </span>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Win rate') }}</span>
                        <span class="q2-stat-val">{{ $kpis['win_rate'] !== null ? $kpis['win_rate'].'%' : '—' }}</span>
                        <span class="q2-stat-var">{{ $kpis['accepted'] }} {{ __('accepted /') }} {{ $kpis['accepted'] + $kpis['declined'] }} {{ __('decided') }}</span>
                    </span>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <span class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Expiring ≤ 7 days') }}</span>
                        <span class="q2-stat-val">{{ $kpis['expiring'] }}</span>
                        <span class="q2-stat-var">{{ $kpis['expiring'] > 0 ? __('Needs attention') : __('All healthy') }}</span>
                    </span>
                </div>
                <div class="q2-stat">
                    <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none"><path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span class="q2-stat-meta">
                        <span class="q2-stat-lbl">{{ __('Converted value') }}</span>
                        <span class="q2-stat-val">{{ $cs }} {{ format_number($kpis['converted_value']) }}</span>
                        <span class="q2-stat-var">{{ $kpis['converted_count'] }} {{ __('invoices raised') }}</span>
                    </span>
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

            {{-- §R5 status filter tiles --}}
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
                                        <th style="width:11%">{{ __('Date') }}</th>
                                        <th style="width:14%">{{ __('Valid Until') }}</th>
                                        <th style="width:12%" class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                                        <th style="width:12%">{{ __('Status') }}</th>
                                        <th style="width:13%" class="q2-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($quotations as $q)
                                        @php
                                            $name = $q->customer->name ?? $q->customer_name ?? '—';
                                            $initials = collect(explode(' ', trim($name)))->filter()->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
                                            $expState = $q->expiryState();
                                            $days = $q->daysUntilExpiry();
                                        @endphp
                                        <tr class="q2-rowlink" onclick="quotRowOpen(event, {{ $q->id }})">
                                            <td>
                                                <a href="{{ route('accounting.quotations.show', $q) }}" class="q2-mono q2-link">{{ $q->quotation_number }}</a>
                                                <span class="q2-subline">{{ $q->reference ?: $statusLabel($q->status) }}</span>
                                            </td>
                                            <td>
                                                <div class="q2-cust">
                                                    <span class="q2-av">{{ $initials }}</span>
                                                    <span class="q2-nm">{{ $name }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $q->quotation_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="q2-vd">
                                                {{ $q->valid_until?->format('M d, Y') ?? '—' }}
                                                @if($expState === 'expired')
                                                    <span class="q2-xc q2-xc--exp">{{ __('Expired') }}</span>
                                                @elseif($expState === 'soon')
                                                    <span class="q2-xc q2-xc--soon">{{ $days }} {{ __('days') }}</span>
                                                @elseif($expState === 'valid')
                                                    <span class="q2-xc q2-xc--ok">{{ __('Valid') }}</span>
                                                @endif
                                            </td>
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
                                                    {{-- view --}}
                                                    <a href="{{ route('accounting.quotations.show', $q) }}" class="q2-ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>

                                                    {{-- edit (locked unless draft — §R9) --}}
                                                    @if($q->isDraft())
                                                        <button type="button" class="q2-ibtn" title="{{ __('Edit draft') }}" aria-label="{{ __('Edit draft') }}" onclick="quotOpenEdit({{ $q->id }})">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </button>
                                                    @else
                                                        <span class="q2-ibtn is-locked" title="{{ __('🔒 Only drafts can be edited') }}" aria-disabled="true">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </span>
                                                    @endif

                                                    {{-- re-quote (expired only — §R7) --}}
                                                    @if($q->isRequotable())
                                                        <button type="button" class="q2-ibtn q2-ibtn--req" title="{{ __('Re-quote (expired)') }}" aria-label="{{ __('Re-quote') }}" onclick="quotOpenRequote({{ $q->id }})">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4v6h6M20 20v-6h-6M20 8a8 8 0 00-14.9-2M4 16a8 8 0 0014.9 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </button>
                                                    @endif

                                                    {{-- print (sent, converted — §2.2) --}}
                                                    @if(in_array($q->status, ['sent', 'converted'], true))
                                                        <a href="{{ route('accounting.quotations.print', $q) }}" class="q2-ibtn" title="{{ __('Print') }}" aria-label="{{ __('Print') }}" target="_blank" rel="noopener">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </a>
                                                    @endif

                                                    {{-- convert (accepted — §2.2) --}}
                                                    @if($q->status === 'accepted')
                                                        @can('quotations.convert')
                                                            <form method="POST" action="{{ route('accounting.quotations.convert-to-invoice', $q) }}" class="inline">
                                                                @csrf
                                                                <button type="submit" class="q2-ibtn" title="{{ __('Convert to invoice') }}" aria-label="{{ __('Convert') }}" onclick="return fbConfirmButton(event, 'Convert this quotation to an invoice?', { type: 'action' })">
                                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    @endif

                                                    {{-- duplicate (declined — §2.2) --}}
                                                    @if($q->status === 'declined')
                                                        <button type="button" class="q2-ibtn" title="{{ __('Duplicate') }}" aria-label="{{ __('Duplicate') }}" onclick="quotOpenRequote({{ $q->id }})">
                                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 9h11v11H9zM5 15H4a1 1 0 01-1-1V4a1 1 0 011-1h10a1 1 0 011 1v1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="q2-empty">
                                                <div class="q2-empty-halo"><svg viewBox="0 0 24 24" fill="none"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                                                <div>{{ __('No quotations found.') }}</div>
                                                <div class="q2-empty-s">{{ __('Adjust your filters or create a new quotation.') }}</div>
                                            </td>
                                        </tr>
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
                    {{-- §R6 needs attention --}}
                    <div class="q2-railcard" style="margin-bottom:1rem">
                        <div class="q2-rail-group" style="display:flex;align-items:center;gap:8px">
                            <svg viewBox="0 0 24 24" fill="none" style="width:14px;height:14px;color:var(--amber-2,#b45309)"><path d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Needs attention') }}
                        </div>
                        @forelse($needsAttention as $attn)
                            @php $aState = $attn->expiryState(); $aDays = $attn->daysUntilExpiry(); @endphp
                            <a href="{{ route('accounting.quotations.show', $attn) }}" class="q2-attn">
                                <span class="q2-attn-n">{{ $attn->quotation_number }}</span>
                                <span class="q2-attn-s">{{ $attn->customer->name ?? '—' }}</span>
                                @if($aState === 'expired')
                                    <span class="q2-xc q2-xc--exp">{{ __('Expired') }}</span>
                                @else
                                    <span class="q2-xc q2-xc--soon">{{ $aDays }}d</span>
                                @endif
                            </a>
                        @empty
                            <div class="q2-attn-empty">{{ __('No open quotes are expired or expiring soon.') }}</div>
                        @endforelse

                        {{-- §6.3 — failed dispatches surfaced with a confirm-gated retry --}}
                        @forelse($emailFailures as $fail)
                            <div class="q2-attn-fail">
                                <div class="q2-attn-fail-t">
                                    <span class="q2-attn-n">{{ $fail->quotation_number }}</span>
                                    <span class="q2-attn-s">{{ $fail->customer?->name ?? $fail->customer_name ?? '—' }}</span>
                                </div>
                                <p class="q2-attn-fail-m">{{ __('Email delivery failed — the recipient was never notified.') }}</p>
                                <div class="q2-attn-fail-acts">
                                    <a href="{{ route('accounting.quotations.show', $fail) }}" class="q2-btn q2-btn--ghost q2-btn--xs">{{ __('Open') }}</a>
                                    <form method="POST" action="{{ route('accounting.quotations.email', $fail) }}"
                                          onsubmit="return fbConfirmSubmit(event, @js(sprintf(__('Retry emailing this quotation to %s?'), $fail->customer?->email ?? $fail->email ?? __('the customer'))))">
                                        @csrf
                                        <input type="hidden" name="return_to" value="register">
                                        <button type="submit" class="q2-btn q2-btn--danger q2-btn--xs">{{ __('Retry email') }}</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                        @endforelse
                    </div>

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

    {{-- §4.1 View modal — R4 row-wide open, R1 no blur, R2/R3 strict dismissal + trailing Close --}}
    <div class="q2-scrim" id="qv-scrim" hidden>
        <div class="q2-mbox q2-mbox--view" role="dialog" aria-modal="true" aria-labelledby="qv-title">
            <div class="q2-mhead">
                <span class="q2-mic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <div class="q2-mhead-left">
                    <span class="q2-mtitle" id="qv-title">{{ __('Quotation') }}</span>
                    <span class="q2-msub" id="qv-ref"></span>
                </div>
                <span class="q2-badge" id="qv-pill"></span>
                <button type="button" class="q2-mclose" data-close="qv-scrim" aria-label="{{ __('Close') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
            </div>
            <div class="q2-mmeta" id="qv-meta"></div>
            <div class="q2-mbody">
                <div class="q2-steps" id="qv-steps"></div>
                <div class="q2-qgrid">
                    <div>
                        <div class="q2-vsec">{{ __('Line items') }}</div>
                        <table class="q2-ltable">
                            <thead><tr><th>{{ __('Description') }}</th><th class="q2-right">{{ __('Qty') }}</th><th class="q2-right">{{ __('Unit price') }}</th><th class="q2-right">{{ __('Amount') }}</th></tr></thead>
                            <tbody id="qv-lines"></tbody>
                        </table>
                        <div class="q2-vsec">{{ __('Customer & terms') }}</div>
                        <div class="q2-mrow"><span class="l">{{ __('Customer') }}</span><span class="v" id="qv-cust">—</span></div>
                        <div class="q2-mrow"><span class="l">{{ __('Email') }}</span><span class="v q2-mono" id="qv-email">—</span></div>
                        <div class="q2-mrow"><span class="l">{{ __('Valid until') }}</span><span class="v" id="qv-valid">—</span></div>
                        <div class="q2-mrow"><span class="l">{{ __('Payment terms on conversion') }}</span><span class="v" id="qv-terms">—</span></div>
                    </div>
                    <div>
                        <div class="q2-vsec">{{ __('Totals') }}</div>
                        <div class="q2-tsum">
                            <div class="row"><span>{{ __('Subtotal') }}</span><b id="qv-sub">0.00</b></div>
                            <div class="row"><span>{{ __('Discount') }}</span><b id="qv-disc">0.00</b></div>
                            <div class="row"><span>{{ __('Tax') }}</span><b id="qv-tax">0.00</b></div>
                            <div class="grand"><span class="l">{{ __('Quotation total') }}</span><span class="v" id="qv-total">0.00</span></div>
                        </div>
                        <div class="q2-vsec">{{ __('Notes to customer') }}</div>
                        <div class="q2-vnotes" id="qv-notes">—</div>
                        <div class="q2-vsec">{{ __('Internal notes') }}</div>
                        <div class="q2-vnotes" id="qv-internal">—</div>
                    </div>
                </div>
            </div>
            <div class="q2-mfoot q2-mfoot--actions" id="qv-foot">
                <span class="q2-mnote"></span>
            </div>
            <form id="qv-action-form" method="POST" style="display:none">@csrf<input type="hidden" name="_method" value="POST"></form>
        </div>
    </div>

    {{-- §4.2 Edit Draft modal — R1 no blur, R2/R3 strict dismissal + trailing Close --}}
    <div class="q2-scrim" id="eq-scrim" hidden>
        <div class="q2-mbox q2-mbox--edit" role="dialog" aria-modal="true" aria-labelledby="eq-title">
            <div class="q2-mhead">
                <span class="q2-mic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <div class="q2-mhead-left">
                    <span class="q2-mtitle" id="eq-title">{{ __('Edit draft') }}</span>
                    <span class="q2-msub" id="eq-ref"></span>
                </div>
                <span class="q2-badge" id="eq-pill"></span>
                <button type="button" class="q2-mclose" data-close="eq-scrim" aria-label="{{ __('Close') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
            </div>
            <form id="eq-form" method="POST" action="#">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="customer_id" id="eq-customer-id">
                <input type="hidden" name="currency" id="eq-currency">
                <div class="q2-mbody">
                    <div class="q2-vsec">{{ __('Customer & terms') }}</div>
                    <div class="q2-g2">
                        <label class="q2-field"><span class="q2-label">{{ __('Customer name') }} *</span><input class="q2-input" type="text" name="customer_name" id="eq-name" required></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Contact person') }}</span><input class="q2-input" type="text" name="contact_person" id="eq-contact"></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Email') }}</span><input class="q2-input" type="email" name="email" id="eq-email"></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Phone') }}</span><input class="q2-input" type="text" name="phone" id="eq-phone"></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Quotation date') }} *</span><input class="q2-input" type="date" name="quotation_date" id="eq-date" required></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Valid until') }} *</span><input class="q2-input" type="date" name="valid_until" id="eq-valid" required></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Payment terms') }}</span>
                            <select class="q2-input q2-select" name="terms_key" id="eq-terms">
                                <option value="receipt">{{ __('Due on receipt') }}</option>
                                <option value="net7">{{ __('Net 7') }}</option>
                                <option value="net15">{{ __('Net 15') }}</option>
                                <option value="net30">{{ __('Net 30') }}</option>
                                <option value="net60">{{ __('Net 60') }}</option>
                            </select>
                        </label>
                        <label class="q2-field"><span class="q2-label">{{ __('Reference') }}</span><input class="q2-input" type="text" name="reference" id="eq-reference"></label>
                    </div>
                    <div class="q2-vsec">{{ __('Line items') }}</div>
                    <table class="q2-ltable q2-ltable--edit">
                        <thead><tr><th>{{ __('Description') }}</th><th class="q2-right">{{ __('Qty') }}</th><th class="q2-right">{{ __('Unit price') }}</th><th class="q2-right">{{ __('Disc %') }}</th><th class="q2-right">{{ __('Amount') }}</th><th></th></tr></thead>
                        <tbody id="eq-lines"></tbody>
                    </table>
                    <button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" id="eq-add" style="margin-top:.6rem">{{ __('Add line') }}</button>
                    <div class="q2-tsum" style="margin-top:.8rem">
                        <div class="row"><span>{{ __('Subtotal') }}</span><b id="eq-sub">0.00</b></div>
                        <div class="row"><span>{{ __('Tax') }}</span><b id="eq-tax">0.00</b></div>
                        <div class="grand"><span class="l">{{ __('Total') }}</span><span class="v" id="eq-total">0.00</span></div>
                    </div>
                    <div class="q2-vsec">{{ __('Notes to customer') }}</div>
                    <textarea class="q2-input" name="memo" id="eq-memo" rows="2" style="width:100%"></textarea>
                    <div class="q2-vsec">{{ __('Internal notes') }}</div>
                    <textarea class="q2-input" name="notes_internal" id="eq-internal" rows="2" style="width:100%"></textarea>
                </div>
                <div class="q2-mfoot q2-mfoot--actions">
                    <span class="q2-mnote">{{ __('Saves to the draft. Finalize issues it as sent.') }}</span>
                    <button type="submit" class="q2-btn q2-btn--cta q2-btn--sm" name="submission_mode" value="finalize_print">{{ __('Finalize & issue') }}</button>
                    <button type="submit" class="q2-btn q2-btn--ghost q2-btn--sm" name="submission_mode" value="save">{{ __('Save changes') }}</button>
                    <button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" data-close="eq-scrim">{{ __('Close') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- §5 Re-quote modal — R1 no blur, R2/R3 strict dismissal + trailing Close --}}
    <div class="q2-scrim" id="rq-scrim" hidden>
        <div class="q2-mbox q2-mbox--edit" role="dialog" aria-modal="true" aria-labelledby="rq-title">
            <div class="q2-mhead">
                <span class="q2-mic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4v6h6M20 20v-6h-6M20 8a8 8 0 00-14.9-2M4 16a8 8 0 0014.9 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <div class="q2-mhead-left">
                    <span class="q2-mtitle" id="rq-title">{{ __('Re-quote') }}</span>
                    <span class="q2-msub" id="rq-src"></span>
                </div>
                <span class="q2-badge q2-badge--draft" id="rq-pill"></span>
                <button type="button" class="q2-mclose" data-close="rq-scrim" aria-label="{{ __('Close') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
            </div>
            <form id="rq-form" method="POST" action="#">
                @csrf
                <input type="hidden" name="customer_id" id="rq-customer-id">
                <input type="hidden" name="mode" id="rq-mode" value="draft">
                <input type="hidden" name="send" id="rq-send" value="0">
                <div class="q2-mbody">
                    <div class="q2-vsec">{{ __('Customer') }}</div>
                    <div class="q2-g2">
                        <label class="q2-field"><span class="q2-label">{{ __('Customer name') }} *</span><input class="q2-input" type="text" name="customer_name" id="rq-name" required></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Email') }}</span><input class="q2-input" type="email" name="email" id="rq-email"></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Phone') }}</span><input class="q2-input" type="text" name="phone" id="rq-phone"></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Reference') }}</span><input class="q2-input" type="text" name="reference" id="rq-reference"></label>
                    </div>
                    <div class="q2-vsec">{{ __('New quotation') }}</div>
                    <div class="q2-g2">
                        <label class="q2-field"><span class="q2-label">{{ __('Quotation date') }} *</span><input class="q2-input" type="date" name="quotation_date" id="rq-date" required></label>
                        <label class="q2-field"><span class="q2-label">{{ __('Validity') }}</span>
                            <select class="q2-input q2-select" name="validity_days" id="rq-validity">
                                <option value="7">7</option>
                                <option value="14" selected>14</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                                <option value="60">60</option>
                            </select>
                        </label>
                    </div>
                    <div class="q2-vsec">{{ __('Line items') }}</div>
                    <table class="q2-ltable q2-ltable--edit">
                        <thead><tr><th>{{ __('Description') }}</th><th class="q2-right">{{ __('Qty') }}</th><th class="q2-right">{{ __('Unit price') }}</th><th class="q2-right">{{ __('Disc %') }}</th><th class="q2-right">{{ __('Amount') }}</th><th></th></tr></thead>
                        <tbody id="rq-lines"></tbody>
                    </table>
                    <button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" id="rq-add" style="margin-top:.6rem">{{ __('Add line') }}</button>
                    <div class="q2-tsum" style="margin-top:.8rem">
                        <div class="row"><span>{{ __('Subtotal') }}</span><b id="rq-sub">0.00</b></div>
                        <div class="row"><span>{{ __('Tax') }}</span><b id="rq-tax">0.00</b></div>
                        <div class="grand"><span class="l">{{ __('Total') }}</span><span class="v" id="rq-total">0.00</span></div>
                    </div>
                    <div class="q2-vsec">{{ __('Notes to customer') }}</div>
                    <textarea class="q2-input" name="memo" id="rq-memo" rows="2" style="width:100%"></textarea>
                </div>
                <div class="q2-mfoot q2-mfoot--actions">
                    <span class="q2-mnote" id="rq-note">{{ __('This quotation will be superseded by the new one.') }}</span>
                    <button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" data-rqsubmit="draft">{{ __('Create draft quotation') }}</button>
                    <span class="q2-split" id="rq-split">
                        <button type="button" class="q2-btn q2-btn--cta q2-btn--sm" id="rq-final-btn" aria-haspopup="true" aria-expanded="false">{{ __('Finalize') }}</button>
                        <div class="q2-dropmenu" id="rq-menu" role="menu" hidden>
                            <button type="button" class="q2-dropitem" data-rqsubmit="finalize" role="menuitem">{{ __('Finalize & create quotation') }}</button>
                            <button type="button" class="q2-dropitem q2-hintable q2-hint-left" data-rqsubmit="send" role="menuitem" id="rq-send-item">
                                {{ __('Finalize & send to customer') }}
                                <span class="q2-tip">{{ __('Provide customer email to activate this button') }}</span>
                            </button>
                        </div>
                    </span>
                    <button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" data-close="rq-scrim">{{ __('Close') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- §G Print preview — A4 voucher sheet --}}
    <div class="pview" id="printView" hidden>
        <div class="ptoolbar">
            <button type="button" class="tb-back" id="pvBack">
                <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                {{ __('Back') }}
            </button>
            <span class="tb-title">{{ __('Print preview') }}<span id="pvRef">—</span></span>
            <span class="tb-spacer"></span>
            <span class="tb-note">{{ __('A4 · Portrait · Ctrl+P also works') }}</span>
            <button type="button" class="tb-btn" id="pvPdf">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6M9 11h2"/></svg>
                {{ __('Save as PDF') }}
            </button>
            <button type="button" class="tb-btn primary" id="pvPrint">
                <svg viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                {{ __('Print') }}
            </button>
        </div>

        <div class="sheet-wrap">
            <div class="sheet" id="qSheet">
                <div class="wm"><span>{{ __('Draft') }}</span></div>
                <div class="sheet-body">
                    <div class="v-head">
                        <div class="brand">
                            <div class="mark">{{ mb_substr($company?->name ?? config('app.name'), 0, 1) }}</div>
                            <div>
                                <div class="bn">{{ $company?->name ?? config('app.name') }}</div>
                                <div class="bl">
                                    {{ $company?->address ?? __('Head Office') }}<br>
                                    @if($company?->tin) {{ __('TIN') }} {{ $company->tin }} @endif
                                </div>
                            </div>
                        </div>
                        <div class="docblock">
                            <div class="dt">{{ __('Quotation') }}</div>
                            <div class="dn" id="qRef">—</div>
                            <div class="dst" id="qStamp"><i></i>{{ __('Issued') }}</div>
                            <div class="dp" id="qPrinted">—</div>
                        </div>
                    </div>

                    <div class="meta">
                        <div class="cell"><div class="l">{{ __('Customer') }}</div><div class="v" id="qmCust">—</div></div>
                        <div class="cell"><div class="l">{{ __('Contact') }}</div><div class="v" id="qmContact">—</div></div>
                        <div class="cell"><div class="l">{{ __('Email') }}</div><div class="v" id="qmEmail">—</div></div>
                        <div class="cell"><div class="l">{{ __('Phone') }}</div><div class="v" id="qmPhone">—</div></div>
                        <div class="cell"><div class="l">{{ __('Quote Date') }}</div><div class="v" id="qmDate">—</div></div>
                        <div class="cell"><div class="l">{{ __('Valid Until') }}</div><div class="v" id="qmValid">—</div></div>
                        <div class="cell"><div class="l">{{ __('Currency') }}</div><div class="v" id="qmCur">—</div></div>
                        <div class="cell"><div class="l">{{ __('Payment Terms') }}</div><div class="v" id="qmTerms">—</div></div>
                        <div class="cell"><div class="l">{{ __('Branch') }}</div><div class="v" id="qmBranch">—</div></div>
                        <div class="cell"><div class="l">{{ __('Cost Centre') }}</div><div class="v" id="qmCC">—</div></div>
                        <div class="cell"><div class="l">{{ __('Prepared By') }}</div><div class="v" id="qmPrep">—</div></div>
                        <div class="cell"><div class="l">{{ __('Reference') }}</div><div class="v mono" id="qmRef">—</div></div>
                    </div>

                    <div class="vlines">
                        <div class="lt">{{ __('Line Items') }}</div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:34px">#</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="r" style="width:70px">{{ __('Qty') }}</th>
                                    <th class="r" style="width:110px">{{ __('Unit Price') }}</th>
                                    <th class="r" style="width:70px">{{ __('Disc %') }}</th>
                                    <th class="r" style="width:90px">{{ __('Tax %') }}</th>
                                    <th class="r" style="width:120px">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody id="qLines"></tbody>
                            <tbody id="qTotals"></tbody>
                        </table>
                    </div>

                    <div class="qpanels">
                        <div class="narr">
                            <div class="nh">{{ __('Notes') }}</div>
                            <div class="nb" id="qNotes">—</div>
                        </div>
                        <div class="narr">
                            <div class="nh">{{ __('Terms & Conditions') }}</div>
                            <div class="tlist" id="qTerms"></div>
                        </div>
                    </div>

                    <div class="sigs">
                        <div class="sig">
                            <div class="rule"></div>
                            <div class="sl">{{ __('Prepared By') }}</div>
                            <div class="sn" id="qPrepared">—</div>
                            <div class="sd" id="qPreparedDate">—</div>
                        </div>
                        <div class="sig">
                            <div class="rule"></div>
                            <div class="sl">{{ __('Customer Acceptance') }}</div>
                            <div class="sn">{{ __('Signature & Date') }}</div>
                            <div class="sd">{{ __('I accept this quotation') }}</div>
                        </div>
                    </div>

                    <div class="v-foot">
                        <div class="fa" id="qAudit">—</div>
                        <div class="fb">{{ __('This quotation is computer generated and is valid for the period stated.') }}</div>
                        <div class="fc" id="qPage">{{ __('PAGE 1 OF 1') }}</div>
                    </div>
                </div>
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

        window.QUOT_VIEW = @json($viewPayload);
        window.QUOT_CS = @json($cs);

        const QUOT_PILL = {
            draft: ['q2-badge--draft', @json(__('Draft'))],
            sent: ['q2-badge--sent', @json(__('Sent'))],
            accepted: ['q2-badge--accepted', @json(__('Accepted'))],
            declined: ['q2-badge--declined', @json(__('Declined'))],
            converted: ['q2-badge--converted', @json(__('Converted'))],
            void: ['q2-badge--void', @json(__('Void'))],
        };
        const QUOT_STEPS = ['draft', 'sent', 'accepted', 'converted'];
        let quotViewing = null;

        function quotEsc(v) {
            return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
        function quotMoney(n) {
            return (Math.round((Number(n) || 0) * 100) / 100).toFixed(2);
        }
        function quotDfmt(s) {
            if (!s) return '—';
            const d = new Date(s + 'T00:00:00');
            if (isNaN(d)) return '—';
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        function quotStepHTML(status) {
            const idx = { draft: 0, sent: 1, accepted: 2, converted: 3 }[status];
            const bad = status === 'declined' || status === 'void';
            let h = '';
            QUOT_STEPS.forEach((s, i) => {
                let cls = 'q2-step';
                if (!bad && i < idx) cls += ' is-done';
                if (!bad && i === idx) cls += (status === 'converted' ? ' is-done' : ' is-cur');
                if (bad && i === 2) cls += ' is-bad is-done';
                h += '<div class="' + cls + '"><span class="dot"></span><span class="lb">' + QUOT_PILL[s][1] + '</span></div>';
            });
            return h;
        }
        function quotFoot(d) {
            const st = d.status;
            const B = (act, label, cls) => '<button type="button" class="q2-btn ' + cls + ' q2-btn--sm" data-qact="' + act + '">' + label + '</button>';
            const req = d.can.requote ? B('requote', @json(__('Re-quote')), 'q2-btn--cta') : '';
            let html;
            if (st === 'draft') {
                html = '<span class="q2-mnote">' + @json(__('Draft — finalize from Edit to issue it.')) + '</span>' + (d.can.edit ? B('edit', @json(__('Edit quotation')), 'q2-btn--ghost') : '') + (d.can.void ? B('void', @json(__('Void quotation')), 'q2-btn--danger') : '') + req;
            } else if (st === 'sent') {
                html = '<span class="q2-mnote">' + @json(__('Awaiting customer response — follow up before expiry.')) + '</span>' + req + B('print', @json(__('Print / PDF')), 'q2-btn--ghost') + (d.can.void ? B('void', @json(__('Void')), 'q2-btn--danger') : '') + (d.can.decline ? B('decline', @json(__('Mark declined')), 'q2-btn--danger') : '') + (d.can.accept ? B('accept', @json(__('Mark accepted')), 'q2-btn--green') : '');
            } else if (st === 'accepted') {
                html = '<span class="q2-mnote">' + @json(__('Accepted — convert to invoice to fulfil.')) + '</span>' + B('print', @json(__('Print / PDF')), 'q2-btn--ghost') + (d.can.convert ? B('convert', @json(__('Convert to invoice')), 'q2-btn--cta') : '');
            } else if (st === 'converted') {
                html = '<span class="q2-mnote">' + @json(__('Converted to invoice — quotation locked.')) + '</span>' + B('print', @json(__('Print / PDF')), 'q2-btn--ghost');
            } else if (st === 'declined') {
                html = '<span class="q2-mnote">' + @json(__('Declined by customer — re-quote or duplicate to revise.')) + '</span>' + req + B('duplicate', @json(__('Duplicate as draft')), 'q2-btn--ghost');
            } else {
                html = '<span class="q2-mnote">' + @json(__('Void — retained for audit only.')) + '</span>';
            }
            html += '<button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" data-close="qv-scrim">' + @json(__('Close')) + '</button>';
            return html;
        }
        function quotOpenView(id) {
            const d = window.QUOT_VIEW[id];
            if (!d) return;
            quotViewing = id;
            document.getElementById('qv-ref').textContent = d.number || '';
            const p = QUOT_PILL[d.status] || ['q2-badge', d.status];
            const pill = document.getElementById('qv-pill');
            pill.className = 'q2-badge ' + p[0];
            pill.innerHTML = '<span class="q2-dot"></span>' + quotEsc(p[1]);

            let meta = '<span class="q2-mchip b">' + quotEsc(d.customer) + '</span>';
            meta += '<span class="q2-mchip">' + @json(__('Quoted')) + ' ' + quotDfmt(d.date) + '</span>';
            meta += '<span class="q2-mchip">' + @json(__('Valid until')) + ' ' + quotDfmt(d.valid) + '</span>';
            meta += '<span class="q2-mchip mono">' + (d.expired ? @json(__('Expired')) : (d.days != null && d.days <= 7 ? d.days + 'd' : @json(__('Valid')))) + '</span>';
            if (d.superseded) meta += '<span class="q2-mchip mono">' + @json(__('Superseded by')) + ' ' + quotEsc(d.superseded) + '</span>';
            if (d.emailed) meta += '<span class="q2-mchip mono">' + @json(__('Emailed to customer')) + '</span>';
            if (d.email_failed) meta += '<span class="q2-mchip mono q2-mchip--fail">' + @json(__('Email failed')) + '</span>';
            meta += '<span class="q2-mchip b">' + @json(__('Total')) + ' ' + quotEsc(window.QUOT_CS) + ' ' + quotMoney(d.total) + '</span>';
            document.getElementById('qv-meta').innerHTML = meta;

            document.getElementById('qv-steps').innerHTML = quotStepHTML(d.status);

            const linesHTML = (d.lines || []).map(l =>
                '<tr><td>' + quotEsc(l.desc) + '</td><td class="q2-right">' + l.qty + '</td><td class="q2-right">' + quotMoney(l.unit) + '</td><td class="q2-right">' + quotMoney(l.amount) + '</td></tr>'
            ).join('');
            document.getElementById('qv-lines').innerHTML = linesHTML || '<tr><td colspan="4" class="q2-empty-s">' + @json(__('No line items.')) + '</td></tr>';

            document.getElementById('qv-sub').textContent = quotMoney(d.sub);
            document.getElementById('qv-disc').textContent = d.disc ? '−' + quotMoney(d.disc) : '0.00';
            document.getElementById('qv-tax').textContent = quotMoney(d.tax);
            document.getElementById('qv-total').textContent = quotEsc(window.QUOT_CS) + ' ' + quotMoney(d.total);
            document.getElementById('qv-cust').textContent = d.customer || '—';
            document.getElementById('qv-email').textContent = d.email || '—';
            document.getElementById('qv-valid').textContent = quotDfmt(d.valid);
            document.getElementById('qv-terms').textContent = d.terms || '—';
            document.getElementById('qv-notes').textContent = d.notes || '—';
            document.getElementById('qv-internal').textContent = d.internal || '—';
            document.getElementById('qv-foot').innerHTML = quotFoot(d);

            const scrim = document.getElementById('qv-scrim');
            scrim.hidden = false;
            document.body.classList.add('q2-modal-open');
            const close = scrim.querySelector('.q2-mclose');
            if (close) close.focus();
        }
        function quotClose(which) {
            const scrim = document.getElementById(which || 'qv-scrim');
            if (scrim) scrim.hidden = true;
            document.body.classList.remove('q2-modal-open');
            quotViewing = null;
        }
        function quotPost(url, msg, type) {
            const run = () => {
                const f = document.getElementById('qv-action-form');
                f.action = url;
                f.querySelector('input[name="_method"]').value = 'POST';
                f.submit();
            };
            if (msg && window.CB && typeof window.CB.confirm === 'function') {
                window.CB.confirm({ title: msg, type: type || 'action', confirmLabel: @json(__('Confirm')), cancelLabel: @json(__('Cancel')) }).then(ok => { if (ok) run(); });
            } else if (msg && !window.confirm(msg)) {
                return;
            } else {
                run();
            }
        }
        function quotRowOpen(e, id) {
            if (e.target.closest('a, button, form, input, select, label')) return;
            quotOpenView(id);
        }

        /* ---------- §G Print preview ---------- */
        let quotPrinting = null;
        function qSet(id, v) { const el = document.getElementById(id); if (el) el.textContent = v == null || v === '' ? '—' : v; }
        function openQuotePrint(id) {
            const d = window.QUOT_VIEW[id];
            if (!d) return;
            quotPrinting = id;
            const cur = d.currency || window.QUOT_CS || '';
            const draft = d.status === 'draft';
            qSet('pvRef', d.number);
            qSet('qRef', d.number);

            const wm = document.querySelector('#qSheet .wm');
            if (wm) wm.style.display = draft ? '' : 'none';
            const stamp = document.getElementById('qStamp');
            if (stamp) {
                stamp.className = 'dst' + (draft ? ' draftst' : '');
                stamp.innerHTML = '<i></i>' + (draft ? @json(__('Draft')) : @json(__('Issued')));
            }
            const now = new Date();
            const printed = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' · ' + now.toTimeString().slice(0, 5);
            qSet('qPrinted', @json(__('Printed')) + ' ' + printed);

            qSet('qmCust', d.customer);
            qSet('qmContact', d.contact);
            qSet('qmEmail', d.email);
            qSet('qmPhone', d.phone);
            qSet('qmDate', quotDfmt(d.date));
            qSet('qmValid', quotDfmt(d.valid));
            qSet('qmCur', d.currency_name ? (cur + ' · ' + d.currency_name) : cur);
            qSet('qmTerms', d.terms);
            qSet('qmBranch', d.branch || @json(__('Head Office')));
            qSet('qmCC', d.cc);
            qSet('qmPrep', d.prepared);
            qSet('qmRef', d.ref_no);

            const rows = (d.lines || []).map((l, i) =>
                '<tr>' +
                    '<td class="no">' + (i + 1) + '</td>' +
                    '<td>' + quotEsc(l.desc) + '</td>' +
                    '<td class="num">' + (Number(l.qty) || 0) + '</td>' +
                    '<td class="num">' + quotMoney(l.unit) + '</td>' +
                    '<td class="num">' + (Number(l.disc) || 0) + '</td>' +
                    '<td class="num">' + (Number(l.tax) || 0) + '</td>' +
                    '<td class="num">' + quotMoney(l.amount) + '</td>' +
                '</tr>'
            ).join('');
            document.getElementById('qLines').innerHTML = rows || '<tr><td colspan="7" class="num">' + @json(__('No line items.')) + '</td></tr>';

            const trow = (label, val, cls) => '<tr class="' + (cls || '') + '"><td class="num" colspan="6" style="text-align:right;text-transform:uppercase;letter-spacing:.14em;font-size:9.5px;color:var(--p-muted)">' + label + '</td><td class="num">' + val + '</td></tr>';
            let tot = trow(@json(__('Subtotal')), cur + ' ' + quotMoney(d.sub));
            if (d.disc) tot += trow(@json(__('Discount')), '−' + cur + ' ' + quotMoney(d.disc));
            tot += trow(@json(__('Tax')), cur + ' ' + quotMoney(d.tax));
            tot += trow(@json(__('Grand Total')), cur + ' ' + quotMoney(d.total), 'totals');
            if (d.words) tot += '<tr class="words"><td colspan="7"><span class="words-l">' + @json(__('Amount in words')) + '</span><span class="words-v">' + quotEsc(d.words) + '</span></td></tr>';
            document.getElementById('qTotals').innerHTML = tot;

            qSet('qNotes', d.notes);
            document.getElementById('qTerms').innerHTML =
                '<div>' + quotEsc(d.terms) + '</div>' +
                '<div>' + @json(__('Quotation number must be referenced on acceptance.')) + '</div>' +
                '<div>' + @json(__('Prices are valid until the date stated above.')) + '</div>';
            qSet('qPrepared', d.prepared);
            qSet('qPreparedDate', quotDfmt(d.date));
            const audit = 'QUOT ' + (d.number || '') + ' · ' + (d.status || '').toUpperCase() + ' · ' + printed + (d.emailed ? ' · EMAILED ' + (d.emailed_to || '') : '');
            qSet('qAudit', audit);

            const pv = document.getElementById('printView');
            pv.hidden = false;
            pv.classList.add('on');
            document.body.classList.add('pv-on');
        }
        function quotPrintClose() {
            const pv = document.getElementById('printView');
            if (!pv) return;
            pv.classList.remove('on');
            pv.hidden = true;
            document.body.classList.remove('pv-on');
            quotPrinting = null;
        }
        document.getElementById('pvBack').addEventListener('click', quotPrintClose);
        document.getElementById('pvPrint').addEventListener('click', function () { window.print(); });
        document.getElementById('pvPdf').addEventListener('click', function () {
            const d = window.QUOT_VIEW[quotPrinting];
            if (d && d.urls && d.urls.pdf) window.open(d.urls.pdf, '_blank', 'noopener');
        });

        document.getElementById('qv-foot').addEventListener('click', function (e) {
            const btn = e.target.closest('[data-qact]');
            if (!btn) return;
            const d = window.QUOT_VIEW[quotViewing];
            if (!d) return;
            const act = btn.getAttribute('data-qact');
            if (act === 'edit') { const vid = quotViewing; quotClose('qv-scrim'); quotOpenEdit(vid); return; }
            if (act === 'print') { openQuotePrint(quotViewing); return; }
            if (act === 'requote' || act === 'duplicate') { const vid = quotViewing; quotClose('qv-scrim'); quotOpenRequote(vid); return; }
            if (act === 'void') { quotPost(d.urls.void, @json(__('Void this quotation? This cannot be undone.')), 'danger'); return; }
            if (act === 'accept') { quotPost(d.urls.accept, @json(__('Mark this quotation as accepted?')), 'action'); return; }
            if (act === 'decline') { quotPost(d.urls.decline, @json(__('Mark this quotation as declined?')), 'danger'); return; }
            if (act === 'convert') { quotPost(d.urls.convert, @json(__('Convert this quotation to an invoice?')), 'action'); return; }
        });

        /* ---------- §4.2 Edit Draft + §5 Re-quote line editors ---------- */
        window.QUOT_DEFAULT_ACCOUNT = @json($defaultIncomeAccountId);
        window.QUOT_TODAY = @json(now()->toDateString());
        window.QUOT_UPDATE_URL = @json(route('accounting.quotations.update', ['quotation' => '__ID__']));
        const QUOT_TERMS_MAP = { receipt: 'receipt', net7: 'net7', net15: 'net15', net30: 'net30', net60: 'net60' };

        function quotLineRows(model, bodyId) {
            const body = document.getElementById(bodyId);
            body.innerHTML = (model.lines || []).map((l, i) =>
                '<tr data-idx="' + i + '">' +
                    '<td><input class="q2-tinput" type="text" data-f="description" value="' + quotEsc(l.desc || '') + '"></td>' +
                    '<td><input class="q2-tinput q2-right" type="number" step="0.01" min="0" data-f="quantity" value="' + (Number(l.qty) || 0) + '"></td>' +
                    '<td><input class="q2-tinput q2-right" type="number" step="0.01" min="0" data-f="unit_price" value="' + (Number(l.unit) || 0) + '"></td>' +
                    '<td><input class="q2-tinput q2-right" type="number" step="0.01" min="0" max="100" data-f="disc_pct" value="' + (Number(l.disc) || 0) + '"></td>' +
                    '<td class="q2-right" data-amt="' + i + '">0.00</td>' +
                    '<td class="q2-hide"><input type="hidden" data-f="income_account_id" value="' + quotEsc(l.account || window.QUOT_DEFAULT_ACCOUNT || '') + '"><input type="hidden" data-f="tax_rate" value="' + (Number(l.tax) || 0) + '"></td>' +
                    '<td><button type="button" class="q2-ibtn q2-ibtn--del" data-line-del="1" aria-label="' + @json(__('Remove')) + '">×</button></td>' +
                '</tr>'
            ).join('');
        }
        function quotRecompute(model, ids) {
            let sub = 0, tax = 0;
            (model.lines || []).forEach((l, i) => {
                const gross = (Number(l.qty) || 0) * (Number(l.unit) || 0);
                const net = gross * (1 - (Number(l.disc) || 0) / 100);
                const t = net * (Number(l.tax) || 0) / 100;
                sub += net; tax += t;
                const cell = document.querySelector('#' + ids.body + ' [data-amt="' + i + '"]');
                if (cell) cell.textContent = quotMoney(net);
            });
            document.getElementById(ids.sub).textContent = quotMoney(sub);
            document.getElementById(ids.tax).textContent = quotMoney(tax);
            document.getElementById(ids.total).textContent = quotEsc(window.QUOT_CS) + ' ' + quotMoney(sub + tax);
        }
        function quotBindEditor(model, ids) {
            const body = document.getElementById(ids.body);
            body.addEventListener('input', function (e) {
                const row = e.target.closest('tr[data-idx]');
                if (!row) return;
                const f = e.target.getAttribute('data-f');
                if (!f) return;
                const i = parseInt(row.getAttribute('data-idx'), 10);
                if (!model.lines[i]) return;
                model.lines[i][f] = e.target.value;
                quotRecompute(model, ids);
            });
            body.addEventListener('click', function (e) {
                const del = e.target.closest('[data-line-del]');
                if (!del) return;
                const row = del.closest('tr[data-idx]');
                const i = parseInt(row.getAttribute('data-idx'), 10);
                model.lines.splice(i, 1);
                if (!model.lines.length) model.lines.push({ desc: '', qty: 1, unit: 0, disc: 0, tax: 0, account: window.QUOT_DEFAULT_ACCOUNT });
                quotLineRows(model, ids.body);
                quotRecompute(model, ids);
            });
            document.getElementById(ids.add).addEventListener('click', function () {
                model.lines.push({ desc: '', qty: 1, unit: 0, disc: 0, tax: 0, account: window.QUOT_DEFAULT_ACCOUNT });
                quotLineRows(model, ids.body);
                quotRecompute(model, ids);
            });
        }
        function quotSerializeLines(model, form) {
            form.querySelectorAll('input[name^="lines["]').forEach(el => el.remove());
            (model.lines || []).forEach((l, i) => {
                const keys = { description: l.desc || '', quantity: l.qty || 0, unit_price: l.unit || 0, disc_pct: l.disc || 0, tax_rate: l.tax || 0, income_account_id: l.account || window.QUOT_DEFAULT_ACCOUNT || '' };
                Object.keys(keys).forEach(k => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'lines[' + i + '][' + k + ']';
                    inp.value = keys[k];
                    form.appendChild(inp);
                });
            });
        }

        const eqModel = { lines: [] };
        function quotOpenEdit(id) {
            const d = window.QUOT_VIEW[id];
            if (!d) return;
            if (d.status !== 'draft') {
                if (window.CB && window.CB.toast) window.CB.toast('warning', quotEsc(d.number) + ' — ' + @json(__('only drafts can be edited')));
                return;
            }
            const f = document.getElementById('eq-form');
            f.action = window.QUOT_UPDATE_URL.replace('__ID__', id);
            document.getElementById('eq-ref').textContent = d.number || '';
            const p = QUOT_PILL[d.status] || ['q2-badge', d.status];
            const pill = document.getElementById('eq-pill');
            pill.className = 'q2-badge ' + p[0];
            pill.innerHTML = '<span class="q2-dot"></span>' + quotEsc(p[1]);
            document.getElementById('eq-customer-id').value = d.customer_id || '';
            document.getElementById('eq-currency').value = d.currency || '';
            document.getElementById('eq-name').value = d.customer && d.customer !== '—' ? d.customer : '';
            document.getElementById('eq-contact').value = d.contact || '';
            document.getElementById('eq-email').value = d.email || '';
            document.getElementById('eq-phone').value = d.phone || '';
            document.getElementById('eq-date').value = d.date || window.QUOT_TODAY;
            document.getElementById('eq-valid').value = d.valid || '';
            document.getElementById('eq-terms').value = QUOT_TERMS_MAP[d.terms_key] || 'receipt';
            document.getElementById('eq-reference').value = d.reference || '';
            document.getElementById('eq-memo').value = d.notes || '';
            document.getElementById('eq-internal').value = d.internal || '';
            eqModel.lines = (d.lines || []).map(l => ({ desc: l.desc, qty: l.qty, unit: l.unit, disc: l.disc, tax: l.tax, account: l.account }));
            if (!eqModel.lines.length) eqModel.lines.push({ desc: '', qty: 1, unit: 0, disc: 0, tax: 0, account: window.QUOT_DEFAULT_ACCOUNT });
            const ids = { body: 'eq-lines', add: 'eq-add', sub: 'eq-sub', tax: 'eq-tax', total: 'eq-total' };
            quotLineRows(eqModel, ids.body);
            quotRecompute(eqModel, ids);
            document.getElementById('eq-scrim').hidden = false;
            document.body.classList.add('q2-modal-open');
            document.getElementById('eq-name').focus();
        }
        document.getElementById('eq-form').addEventListener('submit', function () {
            quotSerializeLines(eqModel, this);
        });

        const rqModel = { lines: [] };
        let rqViewing = null;
        function quotOpenRequote(id) {
            const d = window.QUOT_VIEW[id];
            if (!d) return;
            rqViewing = id;
            const f = document.getElementById('rq-form');
            f.action = d.urls.requote;
            document.getElementById('rq-src').textContent = d.number || '';
            document.getElementById('rq-customer-id').value = d.customer_id || '';
            document.getElementById('rq-name').value = d.customer && d.customer !== '—' ? d.customer : '';
            document.getElementById('rq-email').value = d.email || '';
            document.getElementById('rq-phone').value = d.phone || '';
            document.getElementById('rq-reference').value = '';
            document.getElementById('rq-date').value = window.QUOT_TODAY;
            document.getElementById('rq-validity').value = '14';
            document.getElementById('rq-memo').value = d.notes || '';
            rqModel.lines = (d.lines || []).map(l => ({ desc: l.desc, qty: l.qty, unit: l.unit, disc: l.disc, tax: l.tax, account: l.account }));
            if (!rqModel.lines.length) rqModel.lines.push({ desc: '', qty: 1, unit: 0, disc: 0, tax: 0, account: window.QUOT_DEFAULT_ACCOUNT });
            const ids = { body: 'rq-lines', add: 'rq-add', sub: 'rq-sub', tax: 'rq-tax', total: 'rq-total' };
            quotLineRows(rqModel, ids.body);
            quotRecompute(rqModel, ids);
            document.getElementById('rq-scrim').hidden = false;
            document.body.classList.add('q2-modal-open');
            document.getElementById('rq-name').focus();
            rqEvalSend();
        }
        function rqEmailValid() {
            const el = document.getElementById('rq-email');
            return /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test((el ? el.value : '').trim());
        }
        function rqEvalSend() {
            const item = document.getElementById('rq-send-item');
            if (!item) return;
            const ok = rqEmailValid();
            item.classList.toggle('is-gated', !ok);
            item.setAttribute('aria-disabled', ok ? 'false' : 'true');
        }
        function menuCloseRq() { const m = document.getElementById('rq-menu'); if (m) m.hidden = true; }
        const rqEmailEl = document.getElementById('rq-email');
        if (rqEmailEl) rqEmailEl.addEventListener('input', rqEvalSend);
        const rqFinalBtn = document.getElementById('rq-final-btn');
        if (rqFinalBtn) rqFinalBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const menu = document.getElementById('rq-menu');
            if (!menu) return;
            menu.hidden = !menu.hidden;
            rqFinalBtn.setAttribute('aria-expanded', menu.hidden ? 'false' : 'true');
            if (!menu.hidden) rqEvalSend();
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('#rq-split')) menuCloseRq();
        });
        document.querySelectorAll('#rq-form [data-rqsubmit]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const kind = btn.getAttribute('data-rqsubmit');
                if (kind === 'send' && !rqEmailValid()) {
                    menuCloseRq();
                    CB.toast('warning', @json(__('Email required')), @json(__('Provide customer email to activate this button.')));
                    return;
                }
                menuCloseRq();
                document.getElementById('rq-mode').value = kind === 'draft' ? 'draft' : 'finalize';
                document.getElementById('rq-send').value = kind === 'send' ? '1' : '0';
                quotSerializeLines(rqModel, document.getElementById('rq-form'));
                document.getElementById('rq-form').submit();
            });
        });

        document.querySelectorAll('#eq-scrim [data-close], #rq-scrim [data-close]').forEach(el => {
            el.addEventListener('click', function () { quotClose(el.getAttribute('data-close')); });
        });
        document.getElementById('qv-scrim').addEventListener('click', function (e) {
            const closer = e.target.closest('[data-close]');
            if (closer) quotClose(closer.getAttribute('data-close'));
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            const pv = document.getElementById('printView');
            if (pv && !pv.hidden) { e.preventDefault(); quotPrintClose(); return; }
            for (const which of ['eq-scrim', 'rq-scrim', 'qv-scrim']) {
                const scrim = document.getElementById(which);
                if (scrim && !scrim.hidden) { e.preventDefault(); quotClose(which); return; }
            }
        });
    </script>

    @include('accounting.quotations._success')
</x-app-layout>
