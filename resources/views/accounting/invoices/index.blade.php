<x-app-layout>
<style>
.invm .layout { grid-template-columns: minmax(0, 1fr) 240px !important; }
</style>

    @php
        $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

        $q = (string) ($filters['q'] ?? '');
        $statusF = (string) ($filters['status'] ?? '');
        $viewF = (string) ($filters['view'] ?? '');
        $sort = (string) ($filters['sort'] ?? 'new');
        $period = (string) ($filters['period'] ?? '');
        $periodFrom = (string) ($filters['period_from'] ?? '');
        $periodTo = (string) ($filters['period_to'] ?? '');

        $hasPeriod = $periodFrom !== '' && $periodTo !== '';
        $periodLabel = $hasPeriod
            ? \Carbon\Carbon::parse($periodFrom)->format('d M') . ' – ' . \Carbon\Carbon::parse($periodTo)->format('d M Y')
            : 'No period filter';

        $presets = [
            '' => 'All time',
            'm0' => 'This month',
            'm-1' => 'Last month',
            'd30' => 'Last 30 days',
            'q0' => 'This quarter',
            'ytd' => 'Year to date',
            'custom' => 'Custom range',
        ];

        $sortOptions = [
            'new' => 'Newest first',
            'old' => 'Oldest first',
            'high' => 'Amount high → low',
            'bal' => 'Balance high → low',
            'due' => 'Due soonest',
        ];

        $pillFor = fn (string $status): array => match ($status) {
            'draft' => ['dr', 'Draft'],
            'sent' => ['se', 'Sent'],
            'partial' => ['pa', 'Partially paid'],
            'paid' => ['pd', 'Paid'],
            'void' => ['vo', 'Void'],
            default => ['se', 'Sent'],
        };

        $initials = function (string $name): string {
            $out = '';
            foreach (array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2) as $word) {
                if ($word !== '') {
                    $out .= mb_strtoupper(mb_substr($word, 0, 1));
                }
            }

            return $out !== '' ? $out : '?';
        };

        $money = fn ($n): string => number_format((float) $n, 2);

        // Tile + Views-rail links keep every other active filter (R7/R9).
        $urlFor = function (array $extra = []) use ($filters): string {
            $params = [];
            foreach (['q', 'status', 'view', 'period', 'period_from', 'period_to', 'customer_id'] as $key) {
                $value = (string) ($filters[$key] ?? '');
                if ($value !== '') {
                    $params[$key] = $value;
                }
            }
            $sortValue = (string) ($filters['sort'] ?? 'new');
            if ($sortValue !== '' && $sortValue !== 'new') {
                $params['sort'] = $sortValue;
            }
            foreach ($extra as $key => $value) {
                if ($value === null || $value === '') {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
            }

            return route('accounting.invoices.index', $params);
        };

        $tiles = [
            ['key' => '', 'label' => 'Total', 'count' => $all, 'cls' => 'all'],
            ['key' => 'draft', 'label' => 'Draft', 'count' => $draft, 'cls' => 'dr'],
            ['key' => 'sent', 'label' => 'Sent', 'count' => $sent, 'cls' => 'se'],
            ['key' => 'partial', 'label' => 'Part paid', 'count' => $partial, 'cls' => 'pa'],
            ['key' => 'paid', 'label' => 'Paid', 'count' => $paid, 'cls' => 'pd'],
            ['key' => 'overdue', 'label' => 'Overdue', 'count' => $overdue, 'cls' => 'ov'],
            ['key' => 'void', 'label' => 'Void', 'count' => $void, 'cls' => 'vo'],
        ];

        $views = [
            ['key' => 'all', 'label' => 'All Invoices', 'count' => $all],
            ['key' => 'open', 'label' => 'Open (Sent + Part)', 'count' => $railOpen],
            ['key' => 'draft', 'label' => 'Drafts', 'count' => $draft],
            ['key' => 'paid', 'label' => 'Paid', 'count' => $paid],
            ['key' => 'overdue', 'label' => 'Overdue', 'count' => $overdue],
        ];

        $today = \Carbon\Carbon::today();
        $first = $invoices->firstItem();
        $last = $invoices->lastItem();
        $total = (int) $invoices->total();
        $invoiceWord = $total === 1 ? 'invoice' : 'invoices';
        $pagerWindow = 2;
        $rate = $summary['billed'] > 0 ? $summary['collection_rate'] . '%' : '—';
    @endphp

    <div class="invm">
        <div class="wrap" id="listView">

            <div class="phead">
                <div>
                    <h1>Invoices</h1>
                    <div class="sub">Bill, collect and reconcile customer invoices — with AR aging intelligence and payment tracking.</div>
                </div>
                <div class="pacts">
                    <a class="btn btn-ghost" id="btnExport" href="{{ route('accounting.invoices.export', request()->query()) }}"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>Export</a>
                    <a class="btn btn-cta" id="btnNew" href="{{ route('accounting.invoices.create') }}"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Create Invoice</a>
                </div>
            </div>

            <div class="kpis">
                <div class="kpi" style="--d:40ms"><div class="top"><span class="l">Outstanding AR</span><span class="ic"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg></span></div><div class="v" id="kAR">{{ $cs }} {{ $money($summary['outstanding_ar']) }}</div><span class="n flat" id="kARN">{{ $openCount }} open invoices</span></div>
                <div class="kpi" style="--d:90ms"><div class="top"><span class="l">Overdue</span><span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span></div><div class="v" id="kOv">{{ $overdue }}</div><span class="n bad" id="kOvN">{{ $cs }} {{ $money($summary['overdue_value']) }} at risk</span></div>
                <div class="kpi" style="--d:140ms"><div class="top"><span class="l">Collection rate</span><span class="ic"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></span></div><div class="v" id="kRate">{{ $rate }}</div><span class="n ok" id="kRateN">collected vs billed</span></div>
                <div class="kpi" style="--d:190ms"><div class="top"><span class="l">Total collected</span><span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 10h8M8 14h8"/></svg></span></div><div class="v" id="kCol">{{ $cs }} {{ $money($summary['collected']) }}</div><span class="n ok" id="kColN">{{ $summary['receipt_count'] }} receipts</span></div>
            </div>

            <div>
                <main class="col">
                    <section class="tcard">
                        <form id="inv-list-form" method="GET" action="{{ route('accounting.invoices.index') }}">
                            <input type="hidden" name="status" value="{{ $statusF }}">
                            <input type="hidden" name="view" value="{{ $viewF }}">
                            <div class="toolbar">
                                <div class="searchwrap"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><input class="in" type="text" id="q" name="q" value="{{ $q }}" placeholder="Customer, number, receipt, source…"></div>
                                <select class="sel" id="sort" name="sort">
                                    @foreach ($sortOptions as $key => $label)
                                        <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-ghost" type="button" id="btnPeriod" title="Show / hide the date-period filter"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>Period</button>
                                <span class="cnt" id="cnt">{{ $total }} {{ $invoiceWord }}</span>
                            </div>
                            <div class="pbar" id="periodBar" @unless($period !== '')style="display:none"@endunless>
                                <div class="fld" style="width:180px"><label>Period preset</label>
                                    <select class="sel" id="pPreset" name="period">
                                        @foreach ($presets as $key => $label)
                                            <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fld" style="width:160px"><label>From (issue date)</label><input class="in" type="date" id="pFrom" name="period_from" value="{{ $periodFrom }}" @disabled($period !== 'custom')></div>
                                <div class="fld" style="width:160px"><label>To (issue date)</label><input class="in" type="date" id="pTo" name="period_to" value="{{ $periodTo }}" @disabled($period !== 'custom')></div>
                                <button class="btn btn-ghost btn-sm" type="button" id="pClear"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>Clear</button>
                                <span class="cnt" id="pLabel">{{ $periodLabel }}</span>
                            </div>
                        </form>

                        <div class="stiles" id="stiles">
                            @foreach ($tiles as $tile)
                                <a class="stile{{ ($tile['key'] === '' ? ($statusF === '' && $viewF === '') : $statusF === $tile['key']) ? ' on' : '' }}" data-st="{{ $tile['key'] }}" href="{{ $urlFor(['status' => $tile['key'], 'view' => '']) }}">
                                    @switch($tile['cls'])
                                        @case('all')
                                            <span class="sic all"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6"/></svg></span>
                                        @break
                                        @case('dr')
                                            <span class="sic dr"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z"/></svg></span>
                                        @break
                                        @case('se')
                                            <span class="sic se"><svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg></span>
                                        @break
                                        @case('pa')
                                            <span class="sic pa"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                                        @break
                                        @case('pd')
                                            <span class="sic pd"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span>
                                        @break
                                        @case('ov')
                                            <span class="sic ov"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg></span>
                                        @break
                                        @case('vo')
                                            <span class="sic vo"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M4.9 4.9l14.2 14.2"/></svg></span>
                                        @break
                                    @endswitch
                                    <span><span class="sl">{{ $tile['label'] }}</span><span class="sv" data-c="{{ $tile['key'] }}">{{ $tile['count'] }}</span></span>
                                </a>
                            @endforeach
                        </div>

                        <div class="tscroll">
                        <table>
                            <thead><tr><th>Invoice №</th><th>Customer</th><th>Issue date</th><th>Due date</th><th class="r">Amount ({{ $cs }})</th><th class="r">Paid ({{ $cs }})</th><th class="r">Balance ({{ $cs }})</th><th>Status</th><th class="r">Actions</th></tr></thead>
                            <tbody id="iBody">
                                @forelse ($invoices as $invoice)
                                    @php
                                        $status = $invoice->collectionStatus();
                                        [$pillCls, $pillLabel] = $pillFor($status);
                                        $totalAmt = (float) ($invoice->grand_total ?? $invoice->amount);
                                        $paidAmt = (float) $invoice->amount_paid;
                                        $balance = round($totalAmt - $paidAmt, 2);
                                        $aging = $invoice->aging ?? ['tone' => 'ok', 'label' => 'Current'];
                                        $search = strtolower(trim($invoice->invoice_number . ' ' . $invoice->partyName() . ' ' . (string) $invoice->reference));
                                    @endphp
                                    <tr class="irow" data-id="{{ $invoice->id }}" data-status="{{ $status }}" data-total="{{ $totalAmt }}" data-paid="{{ $paidAmt }}" data-date="{{ $invoice->issue_date?->format('Y-m-d') }}" data-due="{{ $invoice->due_date?->format('Y-m-d') }}" data-search="{{ e($search) }}">
                                        <td><span class="ino" data-act="view">{{ $invoice->invoice_number }}</span></td>
                                        <td><span class="cust"><span class="av">{{ $initials($invoice->partyName()) }}</span><span class="nm">{{ $invoice->partyName() }}</span></span></td>
                                        <td style="white-space:nowrap">{{ $invoice->issue_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="vd">{{ $invoice->due_date?->format('d M Y') ?? '—' }}<span class="xc {{ $aging['tone'] }}">{{ $aging['label'] }}</span></td>
                                        <td class="tot">{{ $money($totalAmt) }}</td>
                                        <td class="tot dim">{{ $money($paidAmt) }}</td>
                                        <td class="tot{{ $balance > 0.005 ? ' bal' : ' dim' }}">{{ $money($balance) }}</td>
                                        <td><span class="pill {{ $pillCls }}"><i></i>{{ $pillLabel }}</span>@if ($invoice->isOverdue()) <span class="xc exp">Overdue</span>@endif</td>
                                        <td class="r" style="white-space:nowrap"><button class="ibtn" data-act="view" title="Open invoice workspace" aria-label="Open invoice workspace"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></td>
                                    </tr>
                                @empty
                                    <tr id="noRes"><td colspan="9"><div class="empty"><div class="halo"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></div><div class="t">No invoices match</div><div class="s">Adjust the search, period or status filter, or create a new invoice.</div></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>

                        <div class="tfoot">
                            <span id="showing">Showing {{ $first ?? 0 }}–{{ $last ?? 0 }} of {{ $total }} {{ $invoiceWord }}</span>
                            <span>Click any row to open the invoice · all actions live in the invoice workspace</span>
                        </div>

                        @if ($invoices->lastPage() > 1)
                            <div class="pager">
                                <span class="pg-lbl">Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }}</span>
                                @if ($invoices->currentPage() > 1)
                                    <a class="btn btn-sm" href="{{ $invoices->url($invoices->currentPage() - 1) }}">&lsaquo; Prev</a>
                                @else
                                    <span class="btn btn-sm is-disabled">&lsaquo; Prev</span>
                                @endif
                                @for ($p = max(1, $invoices->currentPage() - $pagerWindow); $p <= min($invoices->lastPage(), $invoices->currentPage() + $pagerWindow); $p++)
                                    @if ($p === $invoices->currentPage())
                                        <span class="btn btn-sm is-current">{{ $p }}</span>
                                    @else
                                        <a class="btn btn-sm" href="{{ $invoices->url($p) }}">{{ $p }}</a>
                                    @endif
                                @endfor
                                @if ($invoices->currentPage() < $invoices->lastPage())
                                    <a class="btn btn-sm" href="{{ $invoices->url($invoices->currentPage() + 1) }}">Next &rsaquo;</a>
                                @else
                                    <span class="btn btn-sm is-disabled">Next &rsaquo;</span>
                                @endif
                            </div>
                        @endif
                    </section>
                </main>


            </div>
        </div>

        @include('accounting.invoices._workspace-modals')
    </div>
</x-app-layout>
