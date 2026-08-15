<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $statusBadge = fn ($status) => match ($status) {
            'draft' => ['b-draft', __('Draft')],
            'pending_approval' => ['b-pend', __('Pending Approval')],
            'posted' => ['b-post', __('Posted')],
            'rejected' => ['b-rej', __('Rejected')],
            'reversed' => ['b-rev', __('Reversed')],
            default => ['b-gray', ucfirst(str_replace('_', ' ', $status))],
        };
    @endphp

    <div class="suite ex-suite stage pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- crumbs --}}
            <nav class="crumbs">
                <a href="{{ route('accounting.vendors.dashboard') }}">{{ __('Vendor Centre') }}</a>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                <span>{{ __('Payments') }}</span>
            </nav>

            {{-- head --}}
            <div class="page-head">
                <div>
                    <h1>{{ __('Vendor Payments') }}</h1>
                    <p class="sub">{{ __('Record and review payments made to suppliers.') }}</p>
                </div>
                <div class="cluster">
                    <a href="{{ route('accounting.vendor-payments.create') }}" class="btn cta sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14m-7-7h14"/></svg>
                        {{ __('New Payment') }}
                    </a>
                </div>
            </div>

            {{-- approval queue --}}
            @if($approvalQueue->isNotEmpty())
                <div class="card" style="margin-top:16px;border:1px solid var(--border,#DCEAEA)">
                    <div class="sec-head">
                        <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <h2>{{ __('Approval Queue') }}</h2>
                        <span class="chip-t" style="margin-left:auto">{{ $approvalQueue->count() }} {{ __('awaiting approval') }}</span>
                    </div>
                    <div class="li-wrap" style="margin-top:0;border:none;border-radius:0">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:13%">{{ __('Payment №') }}</th>
                                    <th style="width:20%">{{ __('Vendor') }}</th>
                                    <th style="width:11%">{{ __('Date') }}</th>
                                    <th style="width:13%" class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:13%">{{ __('Method') }}</th>
                                    <th style="width:30%">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($approvalQueue as $payment)
                                    <tr>
                                        <td class="mono"><a href="{{ route('accounting.vendor-payments.show', $payment) }}" class="link">{{ $payment->payment_number }}</a></td>
                                        <td style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $payment->vendor->name ?? '—' }}</td>
                                        <td>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="numr">{{ format_number($payment->amount) }}</td>
                                        <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method ?? 'bank_transfer')) }}</td>
                                        <td>
                                            <div class="row-act">
                                                @can('vendor-payments.approve')
                                                <form method="POST" action="{{ route('accounting.vendor-payments.approve', $payment) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn cta sm" onclick="return fbConfirmButton(event, 'Approve and post this payment of ' + '{{ format_number($payment->amount) }}' + '?', { type: 'action' })">{{ __('Approve') }}</button>
                                                </form>
                                                @endcan
                                                @can('vendor-payments.reject')
                                                <form method="POST" action="{{ route('accounting.vendor-payments.reject', $payment) }}" class="inline">
                                                    @csrf
                                                    <input type="text" name="reason" value="" placeholder="Rejection reason" class="input" style="width:12rem;height:2.2rem;font-size:.78rem" required />
                                                    <button type="submit" class="btn danger-o sm" onclick="return fbConfirmButton(event, 'Reject this payment? A reason is required.', { type: 'danger' })">{{ __('Reject') }}</button>
                                                </form>
                                                @endcan
                                                <a href="{{ route('accounting.vendor-payments.show', $payment) }}" class="ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- KPI boxes --}}
            <div class="kpis" style="margin-top:16px">
                <a href="{{ route('accounting.vendor-payments.index') }}" class="fbox @if(!$activeStatus) on @endif">
                    <span class="t t-ink"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span style="min-width:0">
                        <span class="l">{{ __('All') }}</span>
                        <span class="v">{{ number_format($stats['total']) }}</span>
                        <span class="n" style="font-size:10px;color:var(--faint,#8AA5A7);font-weight:600">{{ format_number($stats['amount']) }} {{ $cs }}</span>
                    </span>
                </a>
                <a href="{{ route('accounting.vendor-payments.index', ['status' => 'pending_approval']) }}" class="fbox @if($activeStatus === 'pending_approval') on @endif">
                    <span class="t t-amber"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span style="min-width:0">
                        <span class="l">{{ __('Pending Approval') }}</span>
                        <span class="v">{{ number_format($stats['pending_approval']) }}</span>
                        <span class="n" style="font-size:10px;color:var(--faint,#8AA5A7);font-weight:600">{{ format_number($stats['pending_approval_amount']) }} {{ $cs }}</span>
                    </span>
                </a>
                <a href="{{ route('accounting.vendor-payments.index', ['status' => 'posted']) }}" class="fbox @if($activeStatus === 'posted') on @endif">
                    <span class="t t-mint"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span style="min-width:0">
                        <span class="l">{{ __('Posted') }} <em style="font-style:normal;font-weight:600;opacity:.6">({{ __('month') }})</em></span>
                        <span class="v">{{ number_format($stats['posted_month']) }}</span>
                        <span class="n" style="font-size:10px;color:var(--faint,#8AA5A7);font-weight:600">{{ format_number($stats['posted_month_amount']) }} {{ $cs }}</span>
                    </span>
                </a>
                <a href="{{ route('accounting.vendor-payments.index', ['status' => 'reversed']) }}" class="fbox @if($activeStatus === 'reversed') on @endif">
                    <span class="t t-red"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8m0-5v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span style="min-width:0">
                        <span class="l">{{ __('Reversed') }}</span>
                        <span class="v">{{ number_format($stats['reversed']) }}</span>
                        <span class="n" style="font-size:10px;color:var(--faint,#8AA5A7);font-weight:600">{{ __('cancelled') }}</span>
                    </span>
                </a>
            </div>

            {{-- toolbar --}}
            <form id="vp-list-form" method="GET" action="{{ route('accounting.vendor-payments.index') }}" class="controls" style="margin-top:16px">
                <span class="opt-tag">{{ $activeStatus ? \Illuminate\Support\Str::title(str_replace('_', ' ', $activeStatus)) : __('All Payments') }}</span>
                <div class="search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Vendor, payment #, reference…" autocomplete="off" class="input" oninput="debounceVpSearch(this)" />
                </div>
                <select name="status" class="input" style="width:12rem" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['draft' => __('Draft'), 'pending_approval' => __('Pending Approval'), 'posted' => __('Posted'), 'rejected' => __('Rejected'), 'reversed' => __('Reversed')] as $value => $label)
                        <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="from_date" value="{{ request('from_date') }}" placeholder="From date" class="input" style="width:9rem" onfocus="this.type='date'" />
                <input type="text" name="to_date" value="{{ request('to_date') }}" placeholder="To date" class="input" style="width:9rem" onfocus="this.type='date'" />
                <button type="submit" class="btn ghost sm">{{ __('Filter') }}</button>
                <a href="{{ route('accounting.vendor-payments.index') }}" class="btn ghost sm">{{ __('Clear') }}</a>
                <span class="chip-t" style="margin-left:auto">{{ number_format($stats['total']) }} {{ __('payments') }} · {{ format_number($stats['amount']) }} {{ $cs }}</span>
            </form>

            {{-- payments table --}}
            <div class="card" style="margin-top:16px">
                <div class="li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:13%">{{ __('Payment №') }}</th>
                                <th style="width:19%">{{ __('Vendor') }}</th>
                                <th style="width:10%">{{ __('Date') }}</th>
                                <th style="width:12%">{{ __('Method') }}</th>
                                <th style="width:12%" class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:12%" class="num">{{ __('Applied') }} ({{ $cs }})</th>
                                <th style="width:12%">{{ __('Status') }}</th>
                                <th style="width:10%" class="num">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                @php
                                    [$badgeCls, $badgeLabel] = $statusBadge($payment->status);
                                    $applied = $payment->allocations_sum_amount ?? $payment->allocations->sum('amount');
                                @endphp
                                <tr>
                                    <td class="mono"><a href="{{ route('accounting.vendor-payments.show', $payment) }}" class="link">{{ $payment->payment_number }}</a></td>
                                    <td style="font-weight:600;color:var(--deep-3,#0A2E32)">
                                        @if ($payment->vendor)
                                            <a href="{{ route('accounting.vendors.show', $payment->vendor) }}" class="link">{{ $payment->vendor->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                    <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method ?? 'bank_transfer')) }}</td>
                                    <td class="numr">{{ format_number($payment->amount) }}</td>
                                    <td class="numr">{{ format_number($applied) }}</td>
                                    <td><span class="badge {{ $badgeCls }}"><span class="bdot"></span>{{ $badgeLabel }}</span></td>
                                    <td>
                                        <div class="row-act">
                                            <a href="{{ route('accounting.vendor-payments.show', $payment) }}" class="ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </a>
                                            @if(in_array($payment->status, ['draft', 'pending_approval']))
                                                @can('vendor-payments.create')
                                                <a href="{{ route('accounting.vendor-payments.create', ['vendor_id' => $payment->vendor_id]) }}" class="ibtn" title="{{ __('Pay Again') }}" aria-label="{{ __('Pay Again') }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                                @endcan
                                            @endif
                                            @if($payment->status === 'draft')
                                                @can('vendor-payments.submit')
                                                <form method="POST" action="{{ route('accounting.vendor-payments.submit', $payment) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="ibtn" title="{{ __('Submit for Approval') }}" aria-label="{{ __('Submit for Approval') }}" onclick="return fbConfirmButton(event, 'Submit this payment for approval?', { type: 'action' })">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5m-7 7l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </button>
                                                </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty">{{ __('No payments found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())
                    <div class="pagi">
                        <span class="t">{{ __('Showing') }} {{ $payments->firstItem() }}–{{ $payments->lastItem() }} {{ __('of') }} {{ $payments->total() }} {{ __('payments') }}</span>
                        <span class="p">
                            <a href="{{ $payments->appends(request()->query())->previousPageUrl() }}" class="pg @if($payments->onFirstPage()) ds @endif" aria-label="{{ __('Previous') }}">‹</a>
                            @foreach ($payments->appends(request()->query())->getUrlRange(1, $payments->lastPage()) as $page => $url)
                                @if ($page == $payments->currentPage())
                                    <span class="pg on">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="pg">{{ $page }}</a>
                                @endif
                            @endforeach
                            <a href="{{ $payments->appends(request()->query())->nextPageUrl() }}" class="pg @if(!$payments->hasMorePages()) ds @endif" aria-label="{{ __('Next') }}">›</a>
                        </span>
                    </div>
                @endif
            </div>
        </div>

        @include('accounting.vendors._slim-rail', ['active' => 'payments'])
    </div>

    <script>
        let vpSearchTimer = null;
        function debounceVpSearch(input) {
            clearTimeout(vpSearchTimer);
            vpSearchTimer = setTimeout(() => {
                const form = document.getElementById('vp-list-form');
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }
    </script>
</x-app-layout>
