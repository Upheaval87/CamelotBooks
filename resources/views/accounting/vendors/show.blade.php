@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $totalBilled = (float) $vendor->bills->sum('amount');
    $initialsFor = function ($name) {
        $words = explode(' ', trim((string) $name));
        $ini = '';
        foreach ($words as $w) {
            if (mb_strlen($w) > 0) {
                $ini .= mb_strtoupper(mb_substr($w, 0, 1));
            }
        }
        return mb_substr($ini, 0, 2);
    };
    $txnStatusMap = [
        'draft' => 'b-gray',
        'pending_approval' => 'b-teal',
        'approved' => 'b-teal',
        'partially_paid' => 'b-teal',
        'paid' => 'b-mint',
        'overdue' => 'b-red',
        'void' => 'b-gray',
    ];
@endphp

<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page head --}}
                <div class="page-head">
                    <div>
                        <h1>{{ __('Vendor Detail') }} - {{ $vendor->name }}</h1>
                        <div class="sub">
                            {{ $vendor->email ?? 'No email on file' }}
                            @if ($vendor->phone)
                                · {{ $vendor->phone }}
                            @endif
                        </div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.vendors.index') }}" class="btn ghost sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            All Vendors
                        </a>
                    </div>
                </div>

                {{-- sticky toolbar --}}
                <div class="sticky-head">
                    <div class="toolbar">
                        <div>
                            <div class="glabel">Create</div>
                            <div class="tbtns">
                                <a href="{{ route('accounting.bills.create', ['vendor_id' => $vendor->id]) }}" class="btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg>
                                    New Bill
                                </a>
                                <a href="{{ route('accounting.vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                    New Payment
                                </a>
                            </div>
                        </div>
                        <span class="tdiv"></span>
                        <div>
                            <div class="glabel">Reports</div>
                            <div class="tbtns">
                                <a href="{{ route('accounting.bills.index', ['vendor_id' => $vendor->id]) }}" class="btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/></svg>
                                    Transaction History
                                </a>
                                <a href="{{ route('accounting.reports.vendor-statement', ['vendor_id' => $vendor->id]) }}" class="btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
                                    View Statement
                                </a>
                            </div>
                        </div>
                        <span class="tdiv"></span>
                        <div>
                            <div class="glabel">Document</div>
                            <div class="tbtns">
                                <button type="button" class="btn" onclick="window.print()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
                                    Print
                                </button>
                                @if ($vendor->email)
                                    <a href="mailto:{{ $vendor->email }}" class="btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                        Email Statement
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- profile header --}}
                <div class="card">
                    <div class="prof">
                        <span class="ava-xl">{{ $initialsFor($vendor->name) }}</span>
                        <div>
                            <div class="n">
                                {{ $vendor->name }}
                                @if ($vendor->is_active)
                                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                                @else
                                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                @endif
                            </div>
                            <div class="c">
                                @if ($vendor->email)
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>{{ $vendor->email }}</span>
                                @endif
                                @if ($vendor->phone)
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>{{ $vendor->phone }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="acts">
                            <a href="{{ route('accounting.vendors.edit', $vendor) }}" class="btn cta sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            @can('vendors.void')
                                <form method="POST" action="{{ route('accounting.vendors.toggle', $vendor) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this vendor?') }}', { type: 'danger' })">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn danger-o sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                                        {{ $vendor->is_active ? __('Deactivate') : __('Activate') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- stats --}}
                <div class="sgrid" style="margin-top:16px">
                    <div class="sbox">
                        <div class="l">Open Balance ({{ $cs }})</div>
                        <div class="v {{ $balanceDue > 0 ? 'red' : 'mint' }}">{{ format_number($balanceDue) }}</div>
                    </div>
                    <div class="sbox">
                        <div class="l">Total Billed ({{ $cs }})</div>
                        <div class="v">{{ format_number($totalBilled) }}</div>
                    </div>
                    <div class="sbox">
                        <div class="l">Opening Balance ({{ $cs }})</div>
                        <div class="v">{{ format_number($vendor->opening_balance ?? 0) }}</div>
                    </div>
                </div>

                <div class="shell" style="margin-top:22px">
                    <div class="flex flex-col gap-5 min-w-0">

                        {{-- vendor information --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>
                                <h2>Vendor Information</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g3">
                                <div class="field">
                                    <div class="label">Display Name</div>
                                    <div class="val">{{ $vendor->display_name ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Payment Terms</div>
                                    <div class="val">{{ str_replace('_', ' ', ucfirst($vendor->payment_terms ?? 'due_on_receipt')) }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Payment Terms (Days)</div>
                                    <div class="val">{{ $vendor->payment_terms_days ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Currency</div>
                                    <div class="val mono">{{ $vendor->currency ?? '—' }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Opening Balance</div>
                                    <div class="val">{{ format_number($vendor->opening_balance ?? 0) }}</div>
                                </div>
                                <div class="field">
                                    <div class="label">Opening Balance Date</div>
                                    <div class="val">{{ $vendor->opening_balance_date?->format('M d, Y') ?? '—' }}</div>
                                </div>
                                <div class="field sp3">
                                    <div class="label">Billing Address</div>
                                    <div class="val">{{ $vendor->billing_address ?? '—' }}</div>
                                </div>
                                <div class="field sp3">
                                    <div class="label">Remit To Address</div>
                                    <div class="val">{{ $vendor->remit_to_address ?? '—' }}</div>
                                </div>
                            </div>
                        </section>

                        {{-- activity --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                <h2>Activity</h2>
                                <span class="rule"></span>
                                <span class="chip-t">{{ $transactions->count() }} transactions</span>
                            </div>

                            <div class="tabs">
                                <button type="button" class="tab on" data-tab="tab-transactions">Transactions</button>
                                <button type="button" class="tab" data-tab="tab-statements">Statements</button>
                                <button type="button" class="tab" data-tab="tab-notes">Notes</button>
                            </div>

                            <div class="tpanel" id="tab-transactions">
                                <div class="li-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th class="num">Amount ({{ $cs }})</th>
                                                <th class="num">Paid</th>
                                                <th class="num">Balance</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($transactions as $txn)
                                                @php $tClass = $txnStatusMap[$txn['status']] ?? 'b-gray'; @endphp
                                                <tr>
                                                    <td>
                                                        @if ($txn['type'] === 'Bill')
                                                            <span class="badge b-teal"><span class="bdot"></span>Bill</span>
                                                        @else
                                                            <span class="badge b-mint"><span class="bdot"></span>Payment</span>
                                                        @endif
                                                    </td>
                                                    <td class="em">{{ $txn['date']?->format('M d, Y') ?? '—' }}</td>
                                                    <td class="mono">{{ $txn['reference'] }}</td>
                                                    <td class="numr">{{ format_number(abs($txn['amount'])) }}</td>
                                                    <td class="numr">{{ format_number($txn['paid']) }}</td>
                                                    <td class="numr {{ $txn['balance'] > 0 ? 'red' : '' }}">{{ format_number(abs($txn['balance'])) }}</td>
                                                    <td>
                                                        <span class="badge {{ $tClass }}"><span class="bdot"></span>{{ str_replace('_', ' ', ucfirst($txn['status'])) }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7"><div class="empty">No transactions found.</div></td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tpanel" id="tab-statements" style="display:none">
                                <div class="li-wrap" style="padding:20px 24px">
                                    <div class="field" style="margin-bottom:16px">
                                        <div class="label">Statement</div>
                                        <div class="val">Run a full statement for this vendor covering bills, payments and the running balance.</div>
                                    </div>
                                    <a href="{{ route('accounting.reports.vendor-statement', ['vendor_id' => $vendor->id]) }}" class="btn sec sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
                                        View Full Statement
                                    </a>
                                </div>
                            </div>

                            <div class="tpanel" id="tab-notes" style="display:none">
                                <div class="empty">No notes recorded for this vendor.</div>
                            </div>
                        </section>
                    </div>

                    {{-- right rail --}}
                    <aside>
                        <div class="railsum">
                            <div class="card">
                                <div class="rail-sec">
                                    <div class="sec-head">
                                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                        <h2>Summary</h2>
                                        <span class="rule"></span>
                                    </div>
                                    <div class="vlist" style="margin-top:12px">
                                        <div class="srow"><span class="l">Opening Balance</span><span class="v">{{ format_number($vendor->opening_balance ?? 0) }}</span></div>
                                        <div class="srow"><span class="l">Total Billed</span><span class="v">{{ format_number($totalBilled) }}</span></div>
                                    </div>
                                    <div class="gt"><span class="l">Open Balance</span><span class="v">{{ format_number($balanceDue) }}</span></div>
                                </div>

                                <div class="rail-sec">
                                    <div class="sec-head">
                                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                                        <h2>Quick Nav</h2>
                                        <span class="rule"></span>
                                    </div>
                                    <div class="vlist">
                                        <a href="{{ route('accounting.bills.create', ['vendor_id' => $vendor->id]) }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg></span>
                                            New Bill
                                        </a>
                                        <a href="{{ route('accounting.vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg></span>
                                            Record Payment
                                        </a>
                                        <a href="{{ route('accounting.reports.vendor-statement', ['vendor_id' => $vendor->id]) }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                            View Statement
                                        </a>
                                        <a href="{{ route('accounting.vendors.index') }}" class="vitem">
                                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                            All Vendors
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var root = document.querySelector('.suite');
            if (!root) return;
            root.querySelectorAll('.tab[data-tab]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var target = this.dataset.tab;
                    root.querySelectorAll('.suite .tab').forEach(function (t) { t.classList.remove('on'); });
                    this.classList.add('on');
                    root.querySelectorAll('.tpanel').forEach(function (p) { p.style.display = p.id === target ? 'block' : 'none'; });
                });
            });
        })();
    </script>
</x-app-layout>
