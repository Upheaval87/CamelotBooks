<x-app-layout>
    @php
        $exportParams = request()->except('page');
        $periodLabel = \App\Http\Controllers\Accounting\CashPositionController::periodOptions()[$f['period']] ?? 'This Month';
        $shortPeriods = \App\Http\Controllers\Accounting\CashPositionController::periodShortLabels();
        $from = \Carbon\Carbon::parse($f['date_from'])->format('M j, Y');
        $to = \Carbon\Carbon::parse($f['date_to'])->format('M j, Y');
        $advOpen = ($f['source_module'] || $f['status'] || $f['branch_id'] || $f['cost_center_id'] || $f['reconciled']) ? 'true' : 'false';
        $ledgerParams = array_merge($exportParams, ['date_from' => $f['date_from'], 'date_to' => $f['date_to']]);
    @endphp

    <div class="cp2">
        <div class="wrap">

            <div class="page-head">
                <h1>Cash Position</h1>
                <p class="sub">{{ $periodLabel }} &middot; {{ $from }} &ndash; {{ $to }} &middot; How much cash we hold, where, and what moved.</p>
                <div class="cluster">
                    <a class="btn-ghost" href="{{ $reconciliationUrl }}" aria-label="Reconcile">&#9881; Reconcile</a>
                    <a class="btn-ghost" href="{{ route('accounting.banking.transfers') }}" aria-label="Transfer">&#8646; Transfer</a>
                    <span class="vdiv" aria-hidden="true"></span>
                    <a class="btn-cta" href="{{ $manualTransactionUrl }}">&#43; New Cash Transaction</a>
                    <div class="more" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button type="button" class="btn" aria-label="More options" aria-haspopup="true" :aria-expanded="open" @click="open = !open">&#8943; More</button>
                        <div class="more-menu" x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                            <a class="more-item" href="{{ route('accounting.sales-receipts.create') }}">Record Receipt</a>
                            <a class="more-item" href="{{ route('accounting.expenses.create') }}">Record Payment</a>
                            <a class="more-item" href="{{ route('accounting.cash-position.index', $exportParams) }}">&#8635; Refresh</a>
                            <span class="vdiv" aria-hidden="true"></span>
                            <a class="more-item" href="{{ route('accounting.cash-position.export-csv', $exportParams) }}">&#128216; Export Excel</a>
                            <a class="more-item" href="{{ route('accounting.cash-position.export-pdf', $exportParams) }}">&#128209; Export PDF</a>
                            <a class="more-item" href="{{ route('accounting.cash-position.print', $exportParams) }}">&#128424; Print</a>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="pills" aria-label="Cash &amp; Banking">
                <a class="pill on" href="{{ route('accounting.cash-position.index') }}" aria-current="page">Cash Position</a>
                <a class="pill" href="{{ route('accounting.sales-receipts.index', $exportParams) }}">Cash Receipts</a>
                <a class="pill" href="{{ route('accounting.expenses.index', $exportParams) }}">Cash Payments</a>
                <a class="pill" href="{{ route('accounting.banking.accounts', $exportParams) }}">Bank Accounts</a>
                <a class="pill" href="{{ route('accounting.banking.petty', $exportParams) }}">Cash Accounts</a>
                <a class="pill" href="{{ route('accounting.banking.transfers') }}">Transfers</a>
                <a class="pill" href="{{ $reconciliationUrl }}">Bank Reconciliation</a>
                <a class="pill" href="{{ route('analytics.cash-flow-trend') }}">Cash Forecast</a>
                <a class="pill" href="{{ route('accounting.cash-flow.index') }}">Cash Flow Statement</a>
                <a class="pill" href="{{ route('accounting.general-ledger.index', $ledgerParams) }}">General Ledger</a>
            </nav>

            <form method="GET" action="{{ route('accounting.cash-position.index') }}" id="cp2-form" x-data="{ period: '{{ $f['period'] }}', adv: {{ $advOpen }} }">
                <div class="filterbar">
                    <div class="seg" role="tablist" aria-label="Period">
                        @foreach ($shortPeriods as $val => $label)
                            <button type="button" class="seg-btn" role="tab"
                                    :aria-selected="period === '{{ $val }}'"
                                    :class="period === '{{ $val }}' ? 'on' : ''"
                                    @click="period = '{{ $val }}'">{{ $label }}</button>
                        @endforeach
                    </div>
                    <select class="f" name="branch_id" aria-label="Branch">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $f['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <select class="f" name="account_id" aria-label="Account">
                        <option value="">All accounts</option>
                        @foreach ($accountOptions as $acct)
                            <option value="{{ $acct->id }}" @selected((string) $f['account_id'] === (string) $acct->id)>{{ $acct->code }} &middot; {{ $acct->name }}</option>
                        @endforeach
                    </select>
                    <select class="f" name="currency" aria-label="Currency">
                        <option value="">All currencies</option>
                        @foreach ($currencies as $curOption)
                            <option value="{{ $curOption->code }}" @selected($f['currency'] === $curOption->code)>{{ $curOption->code }}</option>
                        @endforeach
                    </select>
                    <input class="f" type="date" name="date_from" aria-label="From" x-show="period === 'custom'" x-cloak :disabled="period !== 'custom'" value="{{ $f['date_from'] }}">
                    <input class="f" type="date" name="date_to" aria-label="To" x-show="period === 'custom'" x-cloak :disabled="period !== 'custom'" value="{{ $f['date_to'] }}">
                    <button class="btn-cta" type="submit">Apply</button>
                    <a class="btn-ghost" href="{{ route('accounting.cash-position.index') }}">Clear</a>
                    <button class="advlink" type="button" :aria-expanded="adv" @click="adv = !adv">Advanced</button>
                    <input type="hidden" name="period" value="{{ $f['period'] }}" :value="period">
                </div>

                <div class="advpanel" x-show="adv" x-cloak x-transition.opacity>
                    <div class="afield">
                        <label class="alabel" for="cp2-q">Search</label>
                        <input class="f" id="cp2-q" type="text" name="q" value="{{ $f['q'] }}" placeholder="Search accounts &amp; transactions...">
                    </div>
                    <div class="afield">
                        <label class="alabel" for="cp2-type">Transaction Type</label>
                        <select class="f" id="cp2-type" name="source_module">
                            @foreach (\App\Http\Controllers\Accounting\CashPositionController::transactionTypeOptions() as $val => $label)
                                <option value="{{ $val }}" @selected($f['source_module'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="afield">
                        <label class="alabel" for="cp2-status">Status</label>
                        <select class="f" id="cp2-status" name="status">
                            @foreach (\App\Http\Controllers\Accounting\CashPositionController::statusOptions() as $val => $label)
                                <option value="{{ $val }}" @selected($f['status'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="afield">
                        <label class="alabel" for="cp2-cc">Cost Centre</label>
                        <select class="f" id="cp2-cc" name="cost_center_id">
                            <option value="">All cost centres</option>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" @selected((string) $f['cost_center_id'] === (string) $cc->id)>{{ $cc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="afield">
                        <label class="alabel" for="cp2-rec">Reconciliation Status</label>
                        <select class="f" id="cp2-rec" name="reconciled">
                            @foreach (\App\Http\Controllers\Accounting\CashPositionController::reconciledOptions() as $val => $label)
                                <option value="{{ $val }}" @selected($f['reconciled'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            <section class="hero-panel" aria-label="Cash position summary">
                <div class="flow">
                    <div class="fstep">
                        <h3>Opening</h3>
                        <p>{{ $cs }}{{ format_number($totals['opening']) }}</p>
                    </div>
                    <div class="farrow" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                    </div>
                    <div class="fstep">
                        <h3>+ Receipts</h3>
                        <p>{{ $cs }}{{ format_number($totals['receipts']) }}</p>
                        <a href="{{ route('accounting.sales-receipts.index', $exportParams) }}" style="color:#7fe0c3">View receipts &rarr;</a>
                    </div>
                    <div class="farrow" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                    </div>
                    <div class="fstep">
                        <h3>&minus; Payments</h3>
                        <p>{{ $cs }}{{ format_number($totals['payments']) }}</p>
                        <a href="{{ route('accounting.expenses.index', $exportParams) }}" style="color:#ffb4b4">View payments &rarr;</a>
                    </div>
                    <div class="farrow" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                    </div>
                    <div class="fstep close">
                        <h3>Closing</h3>
                        <p>{{ $cs }}{{ format_number($totals['closing']) }}</p>
                    </div>
                </div>
                <div class="chips">
                    <span class="chip"><b>{{ $cs }}{{ format_number($chips['bank']) }}</b> Bank Balance</span>
                    <span class="chip"><b>{{ $cs }}{{ format_number($chips['cash']) }}</b> Cash on Hand</span>
                    <span class="chip warn"><b>{{ $cs }}{{ format_number($chips['unreconciled']) }}</b> Unreconciled &middot; <a href="{{ $reconciliationUrl }}">View unreconciled &rarr;</a></span>
                    <span class="net {{ $totals['net'] < 0 ? 'neg' : '' }}">{{ $totals['net'] >= 0 ? '+' : '&minus;' }} {{ $cs }}{{ format_number(abs($totals['net'])) }} this period</span>
                </div>
            </section>

            <section class="card">
                <div class="card-h">
                    <h2>Cash Position by Account</h2>
                    <span class="n">{{ $movement->count() }} accounts</span>
                </div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Account</th>
                                <th class="num" scope="col">Opening</th>
                                <th class="num" scope="col">Receipts</th>
                                <th class="num" scope="col">Payments</th>
                                <th class="num" scope="col">Transfers In</th>
                                <th class="num" scope="col">Transfers Out</th>
                                <th class="num" scope="col">Closing</th>
                                <th class="num" scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movement as $row)
                                <tr>
                                    <td>
                                        <a class="acct-link" href="{{ route('accounting.general-ledger.account', array_merge([$row['id']], $ledgerParams)) }}">{{ $row['name'] }}</a>
                                        <span class="muted">{{ $row['code'] }}</span>
                                    </td>
                                    <td class="numr">{{ format_number($row['opening']) }}</td>
                                    <td class="numr">{{ format_number($row['receipts']) }}</td>
                                    <td class="numr">{{ format_number($row['payments']) }}</td>
                                    <td class="numr">{{ format_number($row['transfers_in']) }}</td>
                                    <td class="numr">{{ format_number($row['transfers_out']) }}</td>
                                    <td class="numr bold">{{ format_number($row['closing']) }}</td>
                                    <td class="num"><a class="drill" href="{{ route('accounting.general-ledger.account', array_merge([$row['id']], $ledgerParams)) }}">View ledger &rarr;</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">No cash or bank accounts match the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($movement->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td>TOTAL</td>
                                    <td class="numr bold">{{ format_number($totals['opening']) }}</td>
                                    <td class="numr bold">{{ format_number($totals['receipts']) }}</td>
                                    <td class="numr bold">{{ format_number($totals['payments']) }}</td>
                                    <td class="numr bold">{{ format_number($totals['transfers_in']) }}</td>
                                    <td class="numr bold">{{ format_number($totals['transfers_out']) }}</td>
                                    <td class="numr bold">{{ format_number($totals['closing']) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-h">
                    <h2>Cash Movement</h2>
                </div>
                <div class="mov">
                    <div class="in">
                        <h3>Inflows</h3>
                        @forelse ($bars['in'] as $bar)
                            <div class="mrow">
                                <span class="nm">{{ $bar['name'] }} <em>{{ $bar['code'] }}</em></span>
                                <div class="bar" aria-hidden="true"><div class="fill" style="width: {{ max(2, round($bar['value'] / $bars['max_in'] * 100)) }}%"></div></div>
                                <span class="vl">{{ format_number($bar['value']) }}</span>
                            </div>
                        @empty
                            <div class="mrow empty">No receipts in this period.</div>
                        @endforelse
                    </div>
                    <div class="out">
                        <h3>Outflows</h3>
                        @forelse ($bars['out'] as $bar)
                            <div class="mrow">
                                <span class="nm">{{ $bar['name'] }} <em>{{ $bar['code'] }}</em></span>
                                <div class="bar" aria-hidden="true"><div class="fill" style="width: {{ max(2, round($bar['value'] / $bars['max_out'] * 100)) }}%"></div></div>
                                <span class="vl">{{ format_number($bar['value']) }}</span>
                            </div>
                        @empty
                            <div class="mrow empty">No payments in this period.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-h">
                    <h2>Recent Cash Transactions</h2>
                    <span class="n">Last {{ $recent->count() }}</span>
                    <a class="drill" href="{{ route('accounting.general-ledger.index', $ledgerParams) }}">View all &rarr;</a>
                </div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Reference</th>
                                <th scope="col">Description</th>
                                <th scope="col">Account</th>
                                <th class="num" scope="col">Debit</th>
                                <th class="num" scope="col">Credit</th>
                                <th class="num" scope="col">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $txn)
                                <tr>
                                    <td>{{ $txn->date->format('M j, Y') }}</td>
                                    <td><span class="mono">{{ $txn->reference ?: '&mdash;' }}</span></td>
                                    <td>
                                        @if ($txn->journal_entry_id)
                                            <a class="drill" href="{{ route('accounting.journal-entries.show', $txn->journal_entry_id) }}">{{ $txn->description ?: ($txn->journal_number ?: 'Entry') }}</a>
                                        @else
                                            {{ $txn->description ?: 'Transaction' }}
                                        @endif
                                    </td>
                                    <td><a class="acct-link" href="{{ route('accounting.general-ledger.account', array_merge([$txn->bank_account_id], $ledgerParams)) }}">{{ $txn->account_code }} &middot; {{ $txn->account_name }}</a></td>
                                    <td class="numr">{{ $txn->debit > 0 ? format_number($txn->debit) : '' }}</td>
                                    <td class="numr">{{ $txn->credit > 0 ? format_number($txn->credit) : '' }}</td>
                                    <td class="num">
                                        @if ($txn->source_url)
                                            <a class="drill" href="{{ $txn->source_url }}">View</a>
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No bank transactions in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
