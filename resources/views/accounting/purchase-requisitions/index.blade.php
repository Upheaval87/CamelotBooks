<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $activeStatus = request('status', '');
        $fboxes = [
            ['status' => '', 'label' => __('Total'), 't' => 't-ink', 'val' => number_format($statsTotal), 'icon' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z'],
            ['status' => 'submitted', 'label' => __('Pending'), 't' => 't-amber', 'val' => number_format($stats['submitted']->count ?? 0), 'icon' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm-8 4a8 8 0 1116 0 8 8 0 01-16 0z'],
            ['status' => 'approved', 'label' => __('Approved'), 't' => 't-mint', 'val' => number_format($stats['approved']->count ?? 0), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['status' => 'rejected', 'label' => __('Rejected'), 't' => 't-red', 'val' => number_format($stats['rejected']->count ?? 0), 'icon' => 'M6 18L18 6M6 6l12 12'],
            ['status' => 'converted', 'label' => __('Converted'), 't' => 't-teal', 'val' => number_format($stats['converted']->count ?? 0), 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4'],
        ];
        $statusBadges = [
            'draft' => 'b-draft', 'submitted' => 'b-pend', 'approved' => 'b-app',
            'rejected' => 'b-rej', 'converted' => 'b-conv', 'void' => 'b-void',
        ];
        $hasFilters = $activeStatus !== '' || request()->filled('q') || request()->filled('sort') || request()->filled('department') || request()->filled('branch_id');
        $from = $requisitions->firstItem() ?? 0;
        $to = $requisitions->lastItem() ?? 0;
        $total = $requisitions->total();
    @endphp

    <div class="pr-suite wrap">

        <div class="page-head">
            <div>
                <h1>{{ __('Purchase Requisitions') }}</h1>
                <div class="sub">{{ __('Raise, approve and track internal purchase requests.') }}</div>
            </div>
            <div class="cluster">
                <a href="{{ route('accounting.purchase-requisitions.export', request()->except(['page'])) }}" class="btn btn-ghost btn-sm">⇩ {{ __('Export') }}</a>
                <a href="{{ route('accounting.purchase-requisitions.create') }}" class="btn btn-cta">＋ {{ __('Create Requisition') }}</a>
            </div>
        </div>

        <section class="card">
            <div class="card-sec">
                <div class="statgrid">
                    @foreach ($fboxes as $fbox)
                        <button type="button"
                                class="fbox @if ($activeStatus === $fbox['status']) on @endif"
                                onclick="prSetStatus(@js($fbox['status']))"
                                aria-pressed="{{ $activeStatus === $fbox['status'] ? 'true' : 'false' }}">
                            <span class="t {{ $fbox['t'] }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="{{ $fbox['icon'] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span><span class="l">{{ $fbox['label'] }}</span><span class="v">{{ $fbox['val'] }}</span></span>
                        </button>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('accounting.purchase-requisitions.index') }}" id="pr-list-form">
                    <input type="hidden" name="status" id="pr-status" value="{{ $activeStatus }}">
                    <div class="controls">
                        <div class="search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <input class="input" name="q" value="{{ request('q') }}" placeholder="{{ __('Requisition #, requester…') }}"
                                   autocomplete="off" oninput="debouncePrSearch(this)">
                        </div>
                        <select class="input" name="sort" onchange="this.form.submit()">
                            <option value="newest" @if (($f['sort'] ?? 'newest') === 'newest') selected @endif>{{ __('Sort: Newest') }}</option>
                            <option value="total_high" @if (($f['sort'] ?? '') === 'total_high') selected @endif>{{ __('Sort: Total high→low') }}</option>
                            <option value="needed_by" @if (($f['sort'] ?? '') === 'needed_by') selected @endif>{{ __('Sort: Needed by soonest') }}</option>
                        </select>
                        <select class="input" name="department" onchange="this.form.submit()">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}" @if (request('department') === $dept) selected @endif>{{ $dept }}</option>
                            @endforeach
                        </select>
                        <select class="input" name="branch_id" onchange="this.form.submit()">
                            <option value="">{{ __('All Branches') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @if ((string) request('branch_id') === (string) $branch->id) selected @endif>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @if ($hasFilters)
                            <a href="{{ route('accounting.purchase-requisitions.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-sec" style="padding-top:6px">
                <div class="li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:14%">{{ __('Requisition #') }}</th>
                                <th style="width:11%">{{ __('Date') }}</th>
                                <th style="width:17%">{{ __('Requested By') }}</th>
                                <th style="width:14%">{{ __('Department') }}</th>
                                <th style="width:12%">{{ __('Needed By') }}</th>
                                <th class="num" style="width:12%">{{ __('Total') }} ({{ $cs }})</th>
                                <th style="width:10%">{{ __('Status') }}</th>
                                <th style="width:10%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requisitions as $r)
                                <tr>
                                    <td class="mono">
                                        <a href="{{ route('accounting.purchase-requisitions.show', $r) }}" style="color:var(--sec,#128F8E);text-decoration:none">
                                            {{ $r->requisition_number }}
                                        </a>
                                    </td>
                                    <td class="em">{{ $r->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="em">{{ $r->requestedBy?->name ?? $r->createdBy?->name ?? '—' }}</td>
                                    <td class="em">{{ $r->department ?? '—' }}</td>
                                    <td class="em">{{ $r->required_by?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numr">{{ number_format($r->grandTotal(), 2) }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadges[$r->status] ?? 'b-void' }}"><span class="bdot"></span>{{ $r->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        <div class="row-act">
                                            <a class="ibtn" href="{{ route('accounting.purchase-requisitions.show', $r) }}" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                            </a>
                                            @if ($r->status === 'draft' && auth()->user()->can('purchase-requisitions.edit'))
                                                <a class="ibtn" href="{{ route('accounting.purchase-requisitions.edit', $r) }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4L20 8l-4-4L4 16v4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                                </a>
                                                <form method="POST" action="{{ route('accounting.purchase-requisitions.destroy', $r) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ibtn del" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"
                                                            onclick="fbConfirmSubmit(event, 'Delete this requisition?', { type: 'danger' })">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="em" style="text-align:center;padding:28px">
                                        {{ __('No purchase requisitions match the current filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pagi">
                <span class="t">
                    @if ($total > 0)
                        {{ __('Showing') }} {{ $from }}–{{ $to }} {{ __('of') }} {{ $total }} {{ __('requisitions') }}
                    @else
                        {{ __('No requisitions found.') }}
                    @endif
                </span>
                <div style="display:flex;gap:8px">
                    <a class="btn btn-ghost btn-sm @if (!$requisitions->previousPageUrl()) disabled @endif"
                       href="{{ $requisitions->previousPageUrl() ?: '#' }}"
                       aria-disabled="{{ $requisitions->previousPageUrl() ? 'false' : 'true' }}">← {{ __('Prev') }}</a>
                    <a class="btn btn-ghost btn-sm @if (!$requisitions->nextPageUrl()) disabled @endif"
                       href="{{ $requisitions->nextPageUrl() ?: '#' }}"
                       aria-disabled="{{ $requisitions->nextPageUrl() ? 'false' : 'true' }}">{{ __('Next') }} →</a>
                </div>
            </div>
        </section>
    </div>

    <script>
        function prSetStatus(s) {
            document.getElementById('pr-status').value = s || '';
            document.getElementById('pr-list-form').submit();
        }
        let prSearchTimer = null;
        function debouncePrSearch() {
            clearTimeout(prSearchTimer);
            prSearchTimer = setTimeout(() => document.getElementById('pr-list-form').submit(), 350);
        }
    </script>
</x-app-layout>
