<x-app-layout>
    @php
        $lineArr = $undepositedLines->map(fn ($l) => [
            'id' => (int) $l['line_id'],
            'date' => $l['date']?->format('M d, Y') ?? '—',
            'ref' => $l['receipt_number'] ?: ($l['reference'] ?? '—'),
            'desc' => $l['memo'] ?? '—',
            'payment_method' => $l['payment_method'] ?? '—',
            'amount' => (float) $l['amount'],
        ])->values()->all();
    @endphp

    <div class="dp2-suite">
        <div class="dp2-wrap">

            {{-- VIEW 1 · DEPOSITS LIST --}}
            <div class="dp2-phead">
                <div>
                    <h1>{{ __('Deposits') }}</h1>
                    <div class="dp2-sub">{{ __('Record deposits of undeposited receipts into bank accounts.') }}</div>
                </div>
                <div class="dp2-acts">
                    <a href="{{ route('accounting.banking.deposits.create') }}" class="dp2-btn dp2-btn-cta">＋ {{ __('New Deposit') }}</a>
                </div>
            </div>

            {{-- toolbar pills (D2) --}}
            <div class="dp2-tb">
                <a href="{{ route('accounting.banking.dashboard') }}">⌂ {{ __('Banking Centre') }}</a>
                <a href="{{ route('accounting.banking.accounts') }}">▢ {{ __('Bank Accounts') }}</a>
                <a class="dp2-on" href="{{ route('accounting.banking.deposits') }}">↑ {{ __('Deposits') }}</a>
                <a href="{{ route('accounting.banking.cheques') }}">⧉ {{ __('Cheques') }}</a>
                <a href="{{ route('accounting.banking.petty') }}">💰 {{ __('Petty Cash') }}</a>
                <a href="{{ route('accounting.banking.reports') }}">📊 {{ __('Reports') }}</a>
            </div>

            {{-- KPI row (D1) --}}
            <div class="dp2-kpis">
                <div class="dp2-kpi">
                    <span class="dp2-ic">⌂</span>
                    <div>
                        <div class="dp2-l">{{ __('Undeposited Funds') }}</div>
                        <div class="dp2-v">{{ $cs }}{{ format_number($undepositedSum) }}</div>
                    </div>
                </div>
                <div class="dp2-kpi">
                    <span class="dp2-ic dp2-ic--b">▢</span>
                    <div>
                        <div class="dp2-l">{{ __('Bank Accounts') }}</div>
                        <div class="dp2-v">{{ $bankAccountCount }}</div>
                    </div>
                </div>
                <div class="dp2-kpi">
                    <span class="dp2-ic">↑</span>
                    <div>
                        <div class="dp2-l">{{ __('Deposits This Month') }}</div>
                        <div class="dp2-v">{{ $depositsThisMonth }}</div>
                    </div>
                </div>
            </div>

            {{-- Undeposited Receipts --}}
            <div class="dp2-card"
                 x-data="depositsList({ lines: {{ Js::from($lineArr) }}, cs: '{{ $cs }}', createUrl: '{{ route('accounting.banking.deposits.create') }}' })">
                <div class="dp2-card-h">
                    <b>{{ __('Undeposited Receipts') }}</b>
                    <div class="dp2-right">
                        <form method="GET" action="{{ route('accounting.banking.deposits') }}" class="dp2-filter">
                            <select name="payment_method" class="dp2-filter-select" onchange="this.form.submit()">
                                <option value="">{{ __('All payment methods') }}</option>
                                @foreach($paymentMethodList as $pm)
                                    <option value="{{ $pm }}" @selected($pm === $currentPaymentMethod)>{{ $pm }}</option>
                                @endforeach
                            </select>
                            @if($currentPaymentMethod !== '')
                                <a href="{{ route('accounting.banking.deposits') }}" class="dp2-filter-clear" title="{{ __('Clear filter') }}">✕</a>
                            @endif
                        </form>
                        <div class="dp2-selbar" :class="{ 'dp2-on': sel.length > 0 }">
                            <span x-text="sel.length + ' selected · ' + fmt(selTotal)"></span>
                            <a class="dp2-btn dp2-btn-sec dp2-btn-sm" href="#" x-show="sel.length > 0"
                               @click.prevent="goSelected">{{ __('Deposit Selected →') }}</a>
                            <button type="button" class="dp2-btn dp2-btn-ghost dp2-btn-sm" @click="sel = []">{{ __('Clear') }}</button>
                        </div>
                    </div>
                </div>
                <div class="dp2-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:36px"><input type="checkbox" id="chkAll" :checked="allSelected" @change="toggleAll($event.target.checked)"></th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Payment Method') }}</th>
                                <th class="dp2-num">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:70px;text-align:center">{{ __('Deposit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($undepositedLines as $line)
                                <tr>
                                    <td><input type="checkbox" class="dp2-rowchk" :value="{{ $line['line_id'] }}"
                                               :checked="sel.includes({{ $line['line_id'] }})"
                                               @change="toggle({{ $line['line_id'] }}, $event.target.checked)"></td>
                                    <td>{{ $line['date']?->format('M d, Y') ?? '—' }}</td>
                                    <td class="dp2-ref">{{ $line['receipt_number'] ?: ($line['reference'] ?? '—') }}</td>
                                    <td>{{ $line['memo'] ?? '—' }}</td>
                                    <td><span class="dp2-chip">{{ $line['payment_method'] ?? '—' }}</span></td>
                                    <td class="dp2-num">{{ format_number($line['amount']) }}</td>
                                    <td style="text-align:center">
                                        <button type="button" class="dp2-depbtn" title="{{ __('Deposit this receipt') }}"
                                                @click="goOne({{ $line['line_id'] }})">↑</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"><div class="dp2-empty">{{ __('No undeposited receipts.') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        function depositsList(config) {
            return {
                lines: config.lines || [],
                sel: [],
                cs: config.cs || '',
                createUrl: config.createUrl || '',
                get allSelected() {
                    return this.lines.length > 0 && this.sel.length === this.lines.length;
                },
                get selTotal() {
                    return this.lines
                        .filter(r => this.sel.includes(r.id))
                        .reduce((s, r) => s + Number(r.amount || 0), 0);
                },
                fmt(n) {
                    return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
                },
                toggle(id, checked) {
                    if (checked) {
                        if (!this.sel.includes(id)) this.sel.push(id);
                    } else {
                        this.sel = this.sel.filter(x => x !== id);
                    }
                },
                toggleAll(checked) {
                    this.sel = checked ? this.lines.map(r => r.id) : [];
                },
                goSelected() {
                    if (this.sel.length === 0) return;
                    window.location = this.createUrl + '?line_ids=' + this.sel.join(',');
                },
                goOne(id) {
                    window.location = this.createUrl + '?line_ids=' + id;
                },
            };
        }
    </script>
</x-app-layout>
