@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
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
    $vendorCode = 'VEN-' . str_pad((string) $vendor->id, 5, '0', STR_PAD_LEFT);
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
            <div class="suite ex-suite stage">

                {{-- crumbs --}}
                <nav class="crumbs">
                    <a href="{{ route('accounting.vendors.dashboard') }}">Vendor Centre</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                    <a href="{{ route('accounting.vendors.index') }}">Vendors</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                    <span>{{ $vendor->name }}</span>
                </nav>

                {{-- page head --}}
                <div class="page-head">
                    <div>
                        <h1>{{ __('Vendor Detail') }} - {{ $vendor->name }}</h1>
                        <div class="sub">Since {{ $vendor->created_at?->format('M Y') }} · {{ $vendor->is_active ? 'Active' : 'Inactive' }}</div>
                    </div>
                    <div class="cluster">
                        <details class="more">
                            <summary class="btn ghost sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm0 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm0-12a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/></svg>
                                More
                            </summary>
                            <div class="more-menu">
                                <a href="{{ route('accounting.bills.create', ['vendor_id' => $vendor->id]) }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg>
                                    New Bill
                                </a>
                                <a href="{{ route('accounting.vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                    Record Payment
                                </a>
                                <a href="{{ route('accounting.reports.vendor-statement', ['vendor_id' => $vendor->id]) }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
                                    View Statement
                                </a>
                                @if ($vendor->email)
                                <a href="mailto:{{ $vendor->email }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                    Email
                                </a>
                                @endif
                                <a href="{{ route('accounting.vendors.index') }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                    All Vendors
                                </a>
                            </div>
                        </details>
                        <a href="{{ route('accounting.vendors.edit', $vendor) }}" class="btn cta sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </a>
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
                                <span class="mono-chip">{{ $vendorCode }}</span>
                            </div>
                            <div class="c">
                                @if ($vendor->email)
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>{{ $vendor->email }}</span>
                                @endif
                                @if ($vendor->phone)
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>{{ $vendor->phone }}</span>
                                @endif
                                @if ($vendor->billing_address)
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/><circle cx="12" cy="11" r="3"/></svg>{{ $vendor->billing_address }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="acts">
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

                {{-- summary bar --}}
                <div class="sumbar">
                    <div class="cell hero">
                        <div class="l">Open Balance ({{ $cs }})</div>
                        <div class="v">{{ format_number($balanceDue) }}</div>
                        <div class="n">{{ $balanceDue > 0 ? 'Amount owed to this vendor' : 'All settled' }}</div>
                    </div>
                    <div class="cell">
                        <div class="l">Total Billed</div>
                        <div class="v">{{ format_number($totalBilled) }}</div>
                        <div class="n">{{ $vendor->bills->count() }} bills</div>
                    </div>
                    <div class="cell">
                        <div class="l">Opening Balance</div>
                        <div class="v">{{ format_number($vendor->opening_balance ?? 0) }}</div>
                        <div class="n">{{ $vendor->opening_balance_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div class="cell">
                        <div class="l">Payment Terms</div>
                        <div class="v">{{ str_replace('_', ' ', ucfirst($vendor->payment_terms ?? 'due_on_receipt')) }}</div>
                        <div class="n">{{ $vendor->payment_terms_days ? $vendor->payment_terms_days . ' days' : 'Due on receipt' }}</div>
                    </div>
                </div>

                {{-- detail tabs --}}
                <section class="card card-sec" style="margin-top:16px">
                    <div class="tabs">
                        <button type="button" class="tab on" data-tab="tab-overview">Overview</button>
                        <button type="button" class="tab" data-tab="tab-bills">Bills</button>
                        <button type="button" class="tab" data-tab="tab-payments">Payments</button>
                        <button type="button" class="tab" data-tab="tab-credits">Credits</button>
                        <button type="button" class="tab" data-tab="tab-ledger">Ledger</button>
                        <button type="button" class="tab" data-tab="tab-documents">Documents</button>
                        <button type="button" class="tab" data-tab="tab-communication">Communication</button>
                        <button type="button" class="tab" data-tab="tab-evaluation">Evaluation</button>
                    </div>

                    {{-- overview --}}
                    <div class="tpanel" id="tab-overview">
                        <div class="g3" style="margin-top:18px">
                            <div class="field">
                                <div class="label">Display Name</div>
                                <div class="val">{{ $vendor->display_name ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Email</div>
                                <div class="val">{{ $vendor->email ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Phone</div>
                                <div class="val">{{ $vendor->phone ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Currency</div>
                                <div class="val mono">{{ $vendor->currency ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Payment Terms (Days)</div>
                                <div class="val">{{ $vendor->payment_terms_days ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Opening Balance</div>
                                <div class="val">{{ format_number($vendor->opening_balance ?? 0) }}</div>
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
                    </div>

                    {{-- bills --}}
                    <div class="tpanel" id="tab-bills" style="display:none">
                        <div class="li-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th class="num">Amount ({{ $cs }})</th>
                                        <th class="num">Paid</th>
                                        <th class="num">Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($vendor->bills as $bill)
                                        @php $bClass = $txnStatusMap[$bill->status] ?? 'b-gray'; @endphp
                                        <tr>
                                            <td class="mono"><a href="{{ route('accounting.bills.show', $bill) }}">{{ $bill->bill_number }}</a></td>
                                            <td class="em">{{ $bill->bill_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="em">{{ $bill->due_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="numr">{{ format_number($bill->amount) }}</td>
                                            <td class="numr">{{ format_number($bill->amount_paid) }}</td>
                                            <td class="numr {{ $bill->balance_due > 0 ? 'red' : '' }}">{{ format_number($bill->balance_due) }}</td>
                                            <td><span class="badge {{ $bClass }}"><span class="bdot"></span>{{ str_replace('_', ' ', ucfirst($bill->status)) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7"><div class="empty">No bills recorded for this vendor.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- payments --}}
                    <div class="tpanel" id="tab-payments" style="display:none">
                        <div class="li-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Payment #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th class="num">Amount ({{ $cs }})</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($vendor->payments as $payment)
                                        <tr>
                                            <td class="mono"><a href="{{ route('accounting.vendor-payments.show', $payment) }}">{{ $payment->payment_number }}</a></td>
                                            <td class="em">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="em">{{ str_replace('_', ' ', ucfirst($payment->payment_method ?? 'bank_transfer')) }}</td>
                                            <td class="numr mint">{{ format_number($payment->amount) }}</td>
                                            <td><span class="badge b-mint"><span class="bdot"></span>Paid</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5"><div class="empty">No payments recorded for this vendor.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- credits --}}
                    <div class="tpanel" id="tab-credits" style="display:none">
                        <div class="li-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Credit #</th>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th class="num">Amount ({{ $cs }})</th>
                                        <th class="num">Applied</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($credits as $credit)
                                        @php
                                            $cClass = match ($credit->status) {
                                                'draft' => 'b-gray',
                                                'posted' => 'b-teal',
                                                'applied' => 'b-mint',
                                                'void' => 'b-gray',
                                                default => 'b-gray',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="mono"><a href="{{ route('accounting.vendor-credits.show', $credit) }}">{{ $credit->credit_note_number }}</a></td>
                                            <td class="em">{{ $credit->credit_note_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="em">{{ $credit->reference ?? '—' }}</td>
                                            <td class="numr mint">{{ format_number($credit->amount) }}</td>
                                            <td class="numr">{{ format_number($credit->amount_applied) }}</td>
                                            <td><span class="badge {{ $cClass }}"><span class="bdot"></span>{{ ucfirst($credit->status) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6"><div class="empty">No credit notes for this vendor.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ledger --}}
                    <div class="tpanel" id="tab-ledger" style="display:none">
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
                                            <td><span class="badge {{ $tClass }}"><span class="bdot"></span>{{ str_replace('_', ' ', ucfirst($txn['status'])) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7"><div class="empty">No ledger activity for this vendor.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- documents --}}
                    <div class="tpanel" id="tab-documents" style="display:none">
                        <div class="exp-wrap">
                            <div class="empty">No documents attached to this vendor yet.</div>
                        </div>
                    </div>

                    {{-- communication --}}
                    <div class="tpanel" id="tab-communication" style="display:none">
                        <div class="exp-wrap">
                            <div class="empty">No communication history recorded for this vendor.</div>
                        </div>
                    </div>

                    {{-- evaluation --}}
                    <div class="tpanel" id="tab-evaluation" style="display:none">
                        <div class="exp-wrap">
                            <div class="empty">No vendor evaluations recorded yet.</div>
                        </div>
                    </div>
                </section>
            </div>

            @include('accounting.vendors._slim-rail', ['active' => 'vendors'])
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
