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
            ['key' => '', 'label' => __('Total'), 'icon' => 'total', 'cls' => 't-ink', 'val' => number_format($statsTotal)],
            ['key' => 'draft', 'label' => __('Draft'), 'icon' => 'draft', 'cls' => 't-gray', 'val' => number_format($stats['draft']->total ?? 0)],
            ['key' => 'sent', 'label' => __('Sent'), 'icon' => 'sent', 'cls' => 't-teal', 'val' => number_format($stats['sent']->total ?? 0)],
            ['key' => 'partially_paid', 'label' => __('Partially Paid'), 'icon' => 'partially_paid', 'cls' => 't-mint', 'val' => number_format($stats['partially_paid']->total ?? 0)],
            ['key' => 'paid', 'label' => __('Paid'), 'icon' => 'paid', 'cls' => 't-mint', 'val' => number_format($stats['paid']->total ?? 0)],
            ['key' => 'overdue', 'label' => __('Overdue'), 'icon' => 'overdue', 'cls' => 't-red', 'val' => number_format($stats['overdue']->total ?? 0)],
            ['key' => 'void', 'label' => __('Void'), 'icon' => 'void', 'cls' => 't-gray', 'val' => number_format($stats['void']->total ?? 0)],
        ];
        $sortOptions = [
            'date-desc' => __('Newest first'),
            'date-asc' => __('Oldest first'),
            'amount-desc' => __('Amount: high → low'),
            'amount-asc' => __('Amount: low → high'),
            'status' => __('Status'),
        ];
    @endphp

    <div class="suite py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- head --}}
            <div class="page-head">
                <div>
                    <h1>{{ __('Invoices') }}</h1>
                    <p class="sub">{{ __('Track customer invoices and payments.') }}</p>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.invoices.create') }}" class="btn btn-cta">＋ {{ __('Create Invoice') }}</a>
                </div>
            </div>

            {{-- toolbar --}}
            <div class="toolbar">
                <div class="search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer, number, reference…" autocomplete="off"
                           form="inv-list-form" oninput="debounceInvSearch(this)" class="input" />
                </div>
                <select name="sort" form="inv-list-form" class="input" style="width: 13rem" onchange="this.form.submit()">
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

            {{-- status filter boxes --}}
            <div class="statgrid" style="margin-top:16px">
                @foreach($fboxes as $box)
                    <a href="{{ route('accounting.invoices.index', $box['key'] ? ['status' => $box['key']] : []) }}"
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
                                        <th style="width:13%">{{ __('Invoice №') }}</th>
                                        <th style="width:20%">{{ __('Customer') }}</th>
                                        <th style="width:11%">{{ __('Date') }}</th>
                                        <th style="width:11%">{{ __('Due Date') }}</th>
                                        <th style="width:11%" class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                        <th style="width:10%" class="num">{{ __('Paid') }} ({{ $cs }})</th>
                                        <th style="width:11%" class="num">{{ __('Balance') }} ({{ $cs }})</th>
                                        <th style="width:11%">{{ __('Status') }}</th>
                                        <th style="width:8%" class="num">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr>
                                            <td class="mono"><a href="{{ route('accounting.invoices.show', $invoice) }}" class="link">{{ $invoice->invoice_number }}</a></td>
                                            <td style="font-weight:600;color:var(--deep-3,#0A2E32)">{{ $invoice->customer->name ?? '—' }}</td>
                                            <td>{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="numr">{{ format_number($invoice->amount) }}</td>
                                            <td class="numr">{{ format_number($invoice->amount_paid) }}</td>
                                            <td class="numr">{{ format_number($invoice->balance_due) }}</td>
                                            <td>
                                                @switch($invoice->status)
                                                    @case('draft') <span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span> @break
                                                    @case('sent') <span class="badge b-teal"><span class="bdot"></span>{{ __('Sent') }}</span> @break
                                                    @case('partially_paid') <span class="badge b-mint"><span class="bdot"></span>{{ __('Partially Paid') }}</span> @break
                                                    @case('paid') <span class="badge b-act"><span class="bdot"></span>{{ __('Paid') }}</span> @break
                                                    @case('overdue') <span class="badge b-red"><span class="bdot"></span>{{ __('Overdue') }}</span> @break
                                                    @case('void') <span class="badge b-void"><span class="bdot"></span>{{ __('Void') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.invoices.show', $invoice) }}" class="ibtn" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                    @if($invoice->status === 'draft')
                                                        @can('invoices.edit')
                                                        <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="ibtn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        </a>
                                                        @endcan
                                                        @can('invoices.post')
                                                        <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="ibtn" title="{{ __('Post') }}" aria-label="{{ __('Post') }}" onclick="return fbConfirmButton(event, 'Post this invoice?', { type: 'action' })">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                        @endcan
                                                    @endif
                                                    @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                                                        @can('invoices.void')
                                                        <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="ibtn del" title="{{ __('Void') }}" aria-label="{{ __('Void') }}" onclick="return fbConfirmButton(event, 'Are you sure you want to void this invoice?', { type: 'danger' })">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l1.5 13a1 1 0 001 .9h7a1 1 0 001-.9L18 6M4 6h16M9 6l.5-2h5l.5 2M10 10v6M14 10v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="empty">{{ __('No invoices found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($invoices->hasPages())
                            <div class="pagi">
                                <span class="t">{{ __('Showing') }} {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} {{ __('of') }} {{ $invoices->total() }}</span>
                                <div class="pg">
                                    <a href="{{ $invoices->appends(request()->query())->previousPageUrl() }}" class="pgbtn @if($invoices->onFirstPage()) is-disabled @endif" aria-label="{{ __('Previous') }}">‹</a>
                                    @foreach ($invoices->appends(request()->query())->getUrlRange(1, $invoices->lastPage()) as $page => $url)
                                        @if ($page == $invoices->currentPage())
                                            <span class="pgbtn cur">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="pgbtn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    <a href="{{ $invoices->appends(request()->query())->nextPageUrl() }}" class="pgbtn @if(!$invoices->hasMorePages()) is-disabled @endif" aria-label="{{ __('Next') }}">›</a>
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
                            <a href="{{ route('accounting.invoices.index') }}" class="vitem @if(!$activeStatus) on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span>{{ __('All Invoices') }}</span>
                            </a>
                            <a href="{{ route('accounting.invoices.index', ['status' => 'open']) }}" class="vitem @if($activeStatus === 'open') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg></span>
                                <span>{{ __('Open (Draft + Sent)') }}</span>
                            </a>
                            <a href="{{ route('accounting.invoices.index', ['status' => 'draft']) }}" class="vitem @if($activeStatus === 'draft') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Drafts') }}</span>
                            </a>
                            <a href="{{ route('accounting.invoices.index', ['status' => 'sent']) }}" class="vitem @if($activeStatus === 'sent') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Sent') }}</span>
                            </a>
                            <a href="{{ route('accounting.invoices.index', ['status' => 'overdue']) }}" class="vitem @if($activeStatus === 'overdue') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Overdue') }}</span>
                            </a>
                            <a href="{{ route('accounting.invoices.index', ['status' => 'paid']) }}" class="vitem @if($activeStatus === 'paid') on @endif">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('Paid') }}</span>
                            </a>
                        </div>
                        <hr style="border:none;border-top:1px solid var(--line,#E2ECEC);margin:.75rem 4px" />
                        <div class="sec-head">
                            <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <h2>{{ __('Reports') }}</h2>
                        </div>
                        <div class="vlist">
                            <a href="{{ route('accounting.aging.ar-summary') }}" class="vitem">
                                <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span>{{ __('AR Aging Summary') }}</span>
                            </a>
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
