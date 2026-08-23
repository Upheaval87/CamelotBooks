<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Control Account') }}</h1>
                <p class="sub">{{ __('Ledger activity on the net tax control account, with a running balance.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="tx-btn tx-btn-ghost" onclick="window.txExportTable(this, 'tax-control-account')">Export</button>
            </div>
        </div>

        @if ($account)
            <div class="tx-kpis" style="grid-template-columns:repeat(4, 1fr);">
                <div class="tx-chipbox">
                    <span class="l">{{ __('Opening Balance') }}</span>
                    <span class="v">{{ number_format($openingBalance, 2) }}</span>
                </div>
                <div class="tx-chipbox">
                    <span class="l">{{ __('Total Debits') }}</span>
                    <span class="v">{{ number_format($totalDebit, 2) }}</span>
                </div>
                <div class="tx-chipbox">
                    <span class="l">{{ __('Total Credits') }}</span>
                    <span class="v">{{ number_format($totalCredit, 2) }}</span>
                </div>
                <div class="tx-chipbox">
                    <span class="l">{{ __('Closing Balance') }}</span>
                    <span class="v {{ $runningBalance > 0.005 ? 'tx-neg' : '' }}">{{ number_format($runningBalance, 2) }}</span>
                </div>
            </div>

            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#9632;</span>
                    <h2>{{ __('Activity') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
                    <span class="tx-mono tx-em" style="margin-left:auto;">{{ $account->code }} &middot; {{ $account->name }}</span>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:880px;">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Journal') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="num">{{ __('Debit') }}</th>
                                <th class="num">{{ __('Credit') }}</th>
                                <th class="num">{{ __('Running Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $line)
                                <tr>
                                    <td>{{ $line->journalEntry?->date?->format('d M Y') ?? '&mdash;' }}</td>
                                    <td><a class="tx-jl" href="{{ route('accounting.journal-entries.show', $line->journalEntry) }}">{{ $line->journalEntry?->journal_number }}</a></td>
                                    <td class="tx-em">{{ Str::limit($line->journalEntry?->description, 60) }}</td>
                                    <td><span class="tx-badge tx-b-post"><span class="bdot"></span>{{ ucfirst(strtolower($line->journalEntry?->status ?? '')) }}</span></td>
                                    <td class="num">{{ number_format((float) $line->debit, 2) }}</td>
                                    <td class="num">{{ number_format((float) $line->credit, 2) }}</td>
                                    <td class="num"><strong>{{ number_format($line->balance_after, 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted);">No entries posted to this account yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tx-note">
                A positive balance means more tax collected than recoverable — an amount payable to the authority. Amounts exclude the currency symbol.
            </div>
        @else
            <div class="tx-card">
                <div class="tx-pad" style="text-align:center;padding:48px 24px;">
                    <p style="color:var(--sub, #41585c);font-size:13.5px;">{{ __('No net tax control account is configured yet. Map one under Tax Accounts to see ledger activity here.') }}</p>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
