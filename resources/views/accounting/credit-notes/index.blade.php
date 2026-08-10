<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $fboxIcons = [
            'total' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
            'draft' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z',
            'posted' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'applied' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'void' => 'M6 18L18 6M6 6l12 12',
        ];
        $fboxes = [
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 't-ink', 'val' => number_format($stats['total'])],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 't-gray', 'val' => number_format(($stats['by_status']['draft'] ?? 0))],
            ['key' => 'posted', 'label' => __('Posted'), 'icon' => 'posted', 'cls' => 't-teal', 'val' => number_format(($stats['by_status']['posted'] ?? 0))],
            ['key' => 'applied', 'label' => __('Applied'), 'icon' => 'applied', 'cls' => 't-mint', 'val' => number_format(($stats['by_status']['applied'] ?? 0))],
            ['key' => 'void', 'label' => __('Void'), 'icon' => 'void', 'cls' => 't-gray', 'val' => number_format(($stats['by_status']['void'] ?? 0))],
        ];
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- head --}}
            <div class="page-head">
                <div>
                    <h1>{{ __('Credit Notes') }}</h1>
                    <p class="sub">{{ __('Issue credit notes to customers for returns and adjustments.') }}</p>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.credit-notes.create') }}" class="btn btn-cta">＋ {{ __('Create Credit Note') }}</a>
                </div>
            </div>

            {{-- toolbar --}}
            <div class="toolbar">
                <div class="search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer, number, reference…" autocomplete="off"
                           form="cn-list-form" oninput="debounceCnSearch(this)" class="input" />
                </div>
                <select name="status" form="cn-list-form" class="input" style="width: 13rem" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['draft' => __('Draft'), 'posted' => __('Posted'), 'applied' => __('Applied'), 'void' => __('Void')] as $value => $label)
                        <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <form id="cn-list-form" method="GET" action="{{ route('accounting.credit-notes.index') }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}" />
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="from_date" value="{{ request('from_date') }}" />
                <input type="hidden" name="to_date" value="{{ request('to_date') }}" />
            </form>

            {{-- status filter boxes --}}
            <div class="statgrid statgrid--5" style="margin-top:16px">
                @foreach($fboxes as $box)
                    <a href="{{ route('accounting.credit-notes.index', $box['key'] ? ['status' => $box['key']] : []) }}"
                       class="fbox @if($activeStatus === $box['key']) on @endif">
                        <span class="t {{ $box['cls'] }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $fboxIcons[$box['icon']] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span style="min-width:0">
                            <span class="l" style="display:block">{{ $box['label'] }}</span>
                            <span class="v" style="display:block">{{ $box['val'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- shell: main + rail --}}
            <div class="shell" style="margin-top:20px">
                <div>
                    <div class="card">
                        <div class="li-wrap" style="margin-top:0;border:none;border-radius:0">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:12%">{{ __('Credit №') }}</th>
                                        <th style="width:18%">{{ __('Customer') }}</th>
                                        <th style="width:10%">{{ __('Date') }}</th>
                                        <th style="width:12%">{{ __('Reference Invoice') }}</th>
                                        <th style="width:11%" class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                        <th style="width:11%" class="num">{{ __('Applied') }} ({{ $cs }})</th>
                                        <th style="width:11%" class="num">{{ __('Available') }} ({{ $cs }})</th>
                                        <th style="width:11%">{{ __('Status') }}</th>
                                        <th style="width:8%" class="num">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($creditNotes as $creditNote)
                                        <tr>
                                            <td class="mono"><a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="link">{{ $creditNote->credit_note_number }}</a></td>
                                            <td style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $creditNote->customer->name ?? '—' }}</td>
                                            <td>{{ $creditNote->credit_note_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="em">{{ $creditNote->invoice->invoice_number ?? '—' }}</td>
                                            <td class="numr">{{ format_number($creditNote->total) }}</td>
                                            <td class="numr">{{ format_number($creditNote->amount_applied) }}</td>
                                            <td class="numr">{{ format_number($creditNote->available) }}</td>
                                            <td>
                                                @switch($creditNote->status)
                                                    @case('draft') <span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span> @break
                                                    @case('posted') <span class="badge b-teal"><span class="bdot"></span>{{ __('Posted') }}</span> @break
                                                    @case('applied') <span class="badge b-act"><span class="bdot"></span>{{ __('Applied') }}</span> @break
                                                    @case('void') <span class="badge b-void"><span class="bdot"></span>{{ __('Void') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    @if($creditNote->status === 'draft')
                                                        <form method="POST" action="{{ route('accounting.credit-notes.post', $creditNote) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="ibtn" title="{{ __('Post') }}" aria-label="{{ __('Post') }}" onclick="return fbConfirmButton(event, 'Post this credit note?', { type: 'action' })">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="empty">{{ __('No credit notes found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($creditNotes->hasPages())
                            <div class="pagi">
                                <span class="t">{{ __('Showing') }} {{ $creditNotes->firstItem() }}–{{ $creditNotes->lastItem() }} {{ __('of') }} {{ $creditNotes->total() }}</span>
                                <div class="pg">
                                    <a href="{{ $creditNotes->appends(request()->query())->previousPageUrl() }}" class="pgbtn @if($creditNotes->onFirstPage()) is-disabled @endif" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($creditNotes->appends(request()->query())->getUrlRange(1, $creditNotes->lastPage()) as $page => $url)
                                        @if ($page == $creditNotes->currentPage())
                                            <span class="pgbtn cur">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="pgbtn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $creditNotes->appends(request()->query())->nextPageUrl() }}" class="pgbtn @if(!$creditNotes->hasMorePages()) is-disabled @endif" aria-label="{{ __('Next') }}">›</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- rail --}}
                <aside class="railsum">
                    <div class="card rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <h2>{{ __('Views') }}</h2>
                        </div>
                        <div class="vlist">
                            <a href="{{ route('accounting.credit-notes.index') }}" class="vitem @if(!$activeStatus) on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span>{{ __('All Credit Notes') }}</span>
                            </a>
                            <a href="{{ route('accounting.credit-notes.index', ['status' => 'draft']) }}" class="vitem @if($activeStatus === 'draft') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Drafts') }}</span>
                            </a>
                            <a href="{{ route('accounting.credit-notes.index', ['status' => 'posted']) }}" class="vitem @if($activeStatus === 'posted') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Posted') }}</span>
                            </a>
                            <a href="{{ route('accounting.credit-notes.index', ['status' => 'applied']) }}" class="vitem @if($activeStatus === 'applied') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Applied') }}</span>
                            </a>
                        </div>
                        <hr style="border:none;border-top:1px solid var(--line,#E2ECEC);margin:.75rem 4px" />
                        <div class="sec-head">
                            <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <h2>{{ __('Reports') }}</h2>
                        </div>
                        <div class="vlist">
                            <a href="{{ route('accounting.reports.sales-by-customer') }}" class="vitem">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Sales by Customer') }}</span>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        let cnSearchTimer = null;
        function debounceCnSearch(input) {
            clearTimeout(cnSearchTimer);
            cnSearchTimer = setTimeout(() => {
                const form = document.getElementById('cn-list-form');
                form.querySelector('input[name="search"]').value = input.value;
                form.submit();
            }, 350);
        }
    </script>
</x-app-layout>
