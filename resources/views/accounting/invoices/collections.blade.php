<x-app-layout>
    @php
        $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

        // Totals are FULL-queue (services `count` is not the page count) — the
        // KPI strip and #qShowing must describe the whole queue, not the page.
        $total = (int) ($totals['count'] ?? 0);
        $invoiceWord = $total === 1 ? 'invoice' : 'invoices';
        $outstanding = (float) ($totals['outstanding_ar'] ?? 0);
        $overdueCount = (int) ($totals['overdue_count'] ?? 0);
        $dueCount = (int) ($totals['due_7_count'] ?? 0);
        $pagerWindow = 2;
        $today = \Carbon\Carbon::today();

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

        // Spec `agingLabel(d)` — d is signed (negative = days past due), the same
        // direction as agingChipFor's diff, so the text and the chip agree.
        $agingLabel = function ($due) use ($today): string {
            if ($due === null) {
                return '';
            }

            $d = (int) $today->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);

            return match (true) {
                $d < 0 => abs($d) . ' days over',
                $d === 0 => 'Due today',
                default => 'Due in ' . $d . ' days',
            };
        };
    @endphp

    <div class="invm">
        <div class="wrap" id="queueView">

            <div class="phead">
                <div>
                    <h1>Collections Queue</h1>
                    <div class="sub">Every open invoice with a balance — worst aging first, ready for follow-up and payment capture.</div>
                </div>
                <div class="pacts">
                    <a class="btn btn-ghost" href="{{ route('accounting.invoices.index') }}"><svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Back to invoices</a>
                    @can('invoices.export')
                    <a class="btn btn-ghost" href="{{ route('accounting.invoices.collections.export') }}"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>Export queue</a>
                    @endcan
                </div>
            </div>

            <div class="kpis k3">
                <div class="kpi" style="--d:40ms"><div class="top"><span class="l">Outstanding AR</span><span class="ic"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></span></div><div class="v" id="qkAR">{{ $cs }} {{ $money($outstanding) }}</div><span class="n flat" id="qkARN">{{ $total }} open {{ $invoiceWord }}</span></div>
                <div class="kpi" style="--d:90ms"><div class="top"><span class="l">Overdue value</span><span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg></span></div><div class="v" id="qkOv">{{ $cs }} {{ $money($totals['overdue_value'] ?? 0) }}</div><span class="n {{ $overdueCount > 0 ? 'bad' : 'flat' }}" id="qkOvN">{{ $overdueCount }} overdue</span></div>
                <div class="kpi" style="--d:140ms"><div class="top"><span class="l">Due ≤ 7 days</span><span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span></div><div class="v" id="qkDue">{{ $cs }} {{ $money($totals['due_7_value'] ?? 0) }}</div><span class="n {{ $dueCount > 0 ? 'warn' : 'flat' }}" id="qkDueN">{{ $dueCount }} due ≤ 7 days</span></div>
            </div>

            <section class="tcard">
                <div class="tscroll">
                    <table>
                        <thead><tr><th>Invoice №</th><th>Customer</th><th>Contact</th><th>Due date</th><th>Aging</th><th class="r">Balance ({{ $cs }})</th><th>Status</th><th class="r">Actions</th></tr></thead>
                        <tbody id="qTBody">
                            @foreach ($invoices as $invoice)
                                @php
                                    $status = $invoice->collectionStatus();
                                    [$pillCls, $pillLabel] = $pillFor($status);
                                    $aging = $invoice->aging ?? ['tone' => 'ok', 'label' => 'Current'];
                                    $agingText = $agingLabel($invoice->due_date);
                                    $balance = (float) $invoice->balance_due;
                                @endphp
                                <tr class="irow" data-id="{{ $invoice->id }}">
                                    <td><span class="ino" data-act="view">{{ $invoice->invoice_number }}</span></td>
                                    <td><span class="cust"><span class="av">{{ $initials($invoice->partyName()) }}</span><span class="nm">{{ $invoice->partyName() }}</span></span></td>
                                    <td style="font-size:11.5px">{{ $invoice->contact_person ?: '—' }}<br><span style="color:var(--faint);font-size:10px">{{ $invoice->phone ?: '—' }}</span></td>
                                    <td class="vd">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</td>
                                    <td>{{ $agingText }}<span class="xc {{ $aging['tone'] }}">{{ $aging['label'] }}</span></td>
                                    <td class="tot bal">{{ $money($balance) }}</td>
                                    <td><span class="pill {{ $pillCls }}"><i></i>{{ $pillLabel }}</span></td>
                                    <td class="r" style="white-space:nowrap"><button class="ib" data-act="view" title="Open invoice" aria-label="Open invoice workspace"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button><button class="ib payb" data-act="pay" title="Record payment" aria-label="Record payment"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 10h8M8 14h8"/></svg></button></td>
                                </tr>
                            @endforeach
                            {{-- Spec keeps #qNoRes in the DOM; rows are inserted before it (js.txt L301). --}}
                            <tr id="qNoRes" style="{{ $invoices->count() === 0 ? '' : 'display:none' }}"><td colspan="8"><div class="empty"><div class="halo"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></div><div class="t">Queue is clear</div><div class="s">No open invoices with outstanding balances.</div></div></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="tfoot">
                    <span id="qShowing">{{ $total }} {{ $invoiceWord }} in queue · {{ $cs }} {{ $money($outstanding) }} outstanding</span>
                    <span>Row click opens the invoice · green icon records a payment</span>
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
        </div>

        @include('accounting.invoices._workspace-modals')
    </div>
</x-app-layout>
