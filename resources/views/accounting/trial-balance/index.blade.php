<x-app-layout>
    @php
        $cs = $currencySymbol ?? '$';
        $dp = $dp ?? 2;
        $periodLabel = __('As at') . ' ' . \Carbon\Carbon::parse($asOfDate)->format('d M Y');
    @endphp
    <div class="gl-suite">
        <div class="gl-wrap">
            {{-- branded header — hidden on screen, shown in @media print --}}
            @include('accounting.statement-branded-header', [
                'company' => $company,
                'title' => 'Trial Balance',
                'periodLabel' => $periodLabel,
                'currency' => $currency,
                'cs' => $cs,
                'basis' => 'Accrual',
                'preparedBy' => $preparedBy ?? '—',
            ])
            <div class="gl-page-head">
                <div>
                    <h1>Trial Balance</h1>
                    <div class="sub">Summary of all account balances for a period.</div>
                </div>
                <div style="display:flex;gap:10px">
                    <a href="{{ route('accounting.trial-balance.export-csv', request()->query()) }}" class="btn btn-ghost">Export CSV</a>
                    <button type="button" onclick="window.print()" class="btn btn-ghost">🖨 Print</button>
                    <a href="{{ route('accounting.trial-balance.export-pdf', request()->query()) }}" class="btn btn-ghost" target="_blank">⬇ PDF</a>
                </div>
            </div>

            <div class="gl-card gl-mb">
                <div class="gl-pad">
                    <form method="GET" class="gl-fgrid">
                        <div class="gl-f">
                            <label>As of Date</label>
                            <input class="in" type="date" name="date" value="{{ request('date', $asOfDate ?? date('Y-m-d')) }}">
                        </div>
                        <button type="submit" class="btn btn-sec" style="height:42px">Show Trial Balance</button>
                    </form>
                </div>
            </div>

            @php
                $isBalanced = abs($difference) < 0.01;
            @endphp
            <div class="gl-card">
                <div class="gl-card-h">
                    <span class="ic">⚖</span>
                    <h2>All Accounts</h2>
                    <div class="right">
                        @if($isBalanced)
                            <span class="gl-badge gl-b-ok"><span class="bdot"></span>✓ Balanced</span>
                        @else
                            <span class="gl-badge gl-b-rev"><span class="bdot"></span>Out of balance</span>
                        @endif
                    </div>
                </div>
                <div class="gl-li-wrap">
                    <table class="gl-table">
                        <thead>
                            <tr>
                                <th style="width:10%">Account</th>
                                <th style="width:34%">Name</th>
                                <th style="width:12%">Type</th>
                                <th class="num">Debit</th>
                                <th class="num">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trialBalance as $row)
                            @php
                                $acct = $row['account'];
                                $typeClass = match($acct->type) {
                                    'asset' => 'gl-t-asset',
                                    'liability' => 'gl-t-liab',
                                    'equity' => 'gl-t-eq',
                                    'income' => 'gl-t-inc',
                                    'expense' => 'gl-t-exp',
                                    default => 'gl-t-asset',
                                };
                            @endphp
                            <tr>
                                <td class="gl-mono">{{ $acct->code ?? '—' }}</td>
                                <td class="gl-name">{{ $acct->name ?? '—' }}</td>
                                <td><span class="gl-tchip {{ $typeClass }}">{{ $acct->type ?? '—' }}</span></td>
                                <td class="num">{{ ($row['debit_balance'] ?? 0) > 0 ? number_format($row['debit_balance'], $dp) : '—' }}</td>
                                <td class="num">{{ ($row['credit_balance'] ?? 0) > 0 ? number_format($row['credit_balance'], $dp) : '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="gl-empty">
                                    <div class="e">⚖</div>
                                    No data available for the selected date.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($trialBalance) > 0)
                        <tfoot>
                            <tr>
                                <td colspan="3">Total</td>
                                <td class="num">{{ number_format($totalDebit, $dp) }}</td>
                                <td class="num">{{ number_format($totalCredit, $dp) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- branded footer — hidden on screen, shown in @media print --}}
            @php
                $_branchLine = $branchId ? ($branches->firstWhere('id', (int) $branchId)->name ?? null) : null;
                $_orgLine = trim(implode(' · ', array_filter([$_branchLine, $company->tax_id ? 'TPIN '.$company->tax_id : null])));
            @endphp
            <footer class="co-foot">
                <span>{{ $company->name ?? 'Company' }}{{ $_orgLine ? ' · '.$_orgLine : '' }}</span>
                <span class="co-foot-pg">Trial Balance · <span class="co-pageno"></span></span>
            </footer>
        </div>
    </div>

    @push('scripts')
    <script>
    document.querySelectorAll('.co-pageno').forEach(function (el) {
        el.textContent = 'Page 1 of 1';
    });
    </script>
    @endpush
</x-app-layout>
