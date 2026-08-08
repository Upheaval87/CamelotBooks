<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $totalInvoiced = (float) $customer->invoices->sum('amount');
        $txnStatusMap = [
            'draft' => ['label' => 'Draft', 'class' => 'x-badge--gray'],
            'sent' => ['label' => 'Sent', 'class' => 'x-badge--teal'],
            'partially_paid' => ['label' => 'Partially Paid', 'class' => 'x-badge--teal'],
            'paid' => ['label' => 'Paid', 'class' => 'x-badge--mint'],
            'overdue' => ['label' => 'Overdue', 'class' => 'x-badge--red'],
            'void' => ['label' => 'Void', 'class' => 'x-badge--gray'],
        ];
    @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- page head --}}
            <div class="flex items-start justify-between gap-4 flex-wrap pb-4 mb-6 border-b border-line">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">
                        {{ __('Customer Detail') }} - {{ $customer->name }}
                        @if ($customer->is_active)
                            <span class="x-badge x-badge--mint x-head-badge"><span class="x-badge-dot"></span>{{ __('Active') }}</span>
                        @else
                            <span class="x-badge x-badge--gray x-head-badge"><span class="x-badge-dot"></span>{{ __('Inactive') }}</span>
                        @endif
                    </h1>
                    <p class="x-page-sub">
                        {{ $customer->email ?? 'No email on file' }}
                        @if ($customer->phone)
                            · {{ $customer->phone }}
                        @endif
                    </p>
                </div>
                <div class="x-tb">
                    <div class="x-tb-group">
                        <span class="x-tb-label">{{ __('Create') }}</span>
                        <a href="{{ route('accounting.invoices.create', ['customer_id' => $customer->id]) }}" class="x-tb-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg>
                            {{ __('New Invoice') }}
                        </a>
                        <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $customer->id]) }}" class="x-tb-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                            {{ __('New Payment') }}
                        </a>
                    </div>
                    <span class="x-tb-divider"></span>
                    <div class="x-tb-group">
                        <span class="x-tb-label">{{ __('Reports') }}</span>
                        <a href="{{ route('accounting.invoices.index', ['customer_id' => $customer->id]) }}" class="x-tb-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/></svg>
                            {{ __('Transaction History') }}
                        </a>
                        <a href="{{ route('accounting.reports.customer-statement', ['customer_id' => $customer->id]) }}" class="x-tb-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
                            {{ __('View Statement') }}
                        </a>
                    </div>
                    <span class="x-tb-divider"></span>
                    <div class="x-tb-group">
                        <span class="x-tb-label">{{ __('Document') }}</span>
                        <button type="button" class="x-tb-btn" onclick="window.print()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
                            {{ __('Print') }}
                        </button>
                        @if ($customer->email)
                            <a href="mailto:{{ $customer->email }}" class="x-tb-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                {{ __('Email Statement') }}
                            </a>
                        @endif
                    </div>
                    <span class="x-tb-spacer"></span>
                    <a href="{{ route('accounting.customers.edit', $customer) }}" class="x-tb-btn x-tb-btn--cta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        {{ __('Edit') }}
                    </a>
                    @can('customers.void')
                        <form method="POST" action="{{ route('accounting.customers.toggle', $customer) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to archive this customer?') }}', { type: 'danger' })">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="x-tb-btn x-tb-btn--danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                                {{ $customer->is_active ? __('Archive') : __('Activate') }}
                            </button>
                        </form>
                    @endcan
                    <a href="{{ route('accounting.customers.index') }}" class="x-tb-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('All Customers') }}
                    </a>
                </div>
            </div>

            <div class="x-port">
                <div class="x-port-card">
                    <span class="x-port-ic x-port-ic--{{ $balanceDue > 0 ? 'red' : 'mint' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <div>
                        <div class="x-port-lbl">{{ __('Open Balance') }} ({{ $cs }})</div>
                        <div class="x-port-num {{ $balanceDue > 0 ? 'x-port-num--red' : '' }}">{{ format_number($balanceDue) }}</div>
                    </div>
                </div>
                <div class="x-port-card">
                    <span class="x-port-ic x-port-ic--teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                    </span>
                    <div>
                        <div class="x-port-lbl">{{ __('Total Invoiced') }} ({{ $cs }})</div>
                        <div class="x-port-num">{{ format_number($totalInvoiced) }}</div>
                    </div>
                </div>
                <div class="x-port-card">
                    <span class="x-port-ic x-port-ic--mint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    </span>
                    <div>
                        <div class="x-port-lbl">{{ __('Credit Limit') }} ({{ $cs }})</div>
                        <div class="x-port-num">{{ format_number($customer->credit_limit ?? 0) }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 items-start lg:grid-cols-[1fr_340px]">
                <div class="flex flex-col gap-5 min-w-0">

                    {{-- info card --}}
                    <section class="card rounded-[20px] p-6 xl:p-[26px]">
                        <div class="x-sec">
                            <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>
                            <h2 class="x-sec-h2">{{ __('Customer Information') }}</h2>
                            <span class="x-sec-rule"></span>
                        </div>
                        <div class="detail-grid">
                            <x-detail-field :label="__('Display Name')" :value="$customer->display_name ?? '—'" />
                            <x-detail-field :label="__('Payment Terms')" :value="str_replace('_', ' ', ucfirst($customer->payment_terms ?? 'due_on_receipt'))" />
                            <x-detail-field :label="__('Payment Terms (Days)')" :value="$customer->payment_terms_days ?? '—'" />
                            <x-detail-field :label="__('Currency')" :value="$customer->currency ?? '—'" />
                            <x-detail-field :label="__('Opening Balance')" :value="format_money($customer->opening_balance ?? 0)" />
                            <x-detail-field :label="__('Opening Balance Date')" :value="$customer->opening_balance_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Billing Address')">{{ $customer->billing_address ?? '—' }}</x-detail-field>
                            <x-detail-field :label="__('Shipping Address')">{{ $customer->shipping_address ?? '—' }}</x-detail-field>
                        </div>
                    </section>

                    {{-- recent transactions --}}
                    <section class="card rounded-[20px] p-6 xl:p-[26px]">
                        <div class="x-sec">
                            <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                            <h2 class="x-sec-h2">{{ __('Transaction History') }}</h2>
                            <span class="x-sec-rule"></span>
                            <span class="x-chip">{{ $transactions->count() }} {{ __('transactions') }}</span>
                        </div>

                        <div class="mt-4 border border-shell rounded-[14px] overflow-visible round-thead-clip bg-[#fbfcfe]">
                            <table class="x-wset-recent w-full border-collapse text-[13px] table-fixed">
                                <thead>
                                    <tr>
                                        <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Type') }}</th>
                                        <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Date') }}</th>
                                        <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Reference') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Amount') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Paid') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Balance') }}</th>
                                        <th class="py-[11px] px-2.5 text-center text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transactions as $txn)
                                        @php $tStatus = $txnStatusMap[$txn['status']] ?? ['label' => ucfirst($txn['status']), 'class' => 'x-badge--gray']; @endphp
                                        <tr>
                                            <td class="py-3 px-2.5 border-b border-line align-middle">
                                                @if ($txn['type'] === 'Invoice')
                                                    <span class="x-badge x-badge--teal">{{ __('Invoice') }}</span>
                                                @else
                                                    <span class="x-badge x-badge--mint">{{ __('Payment') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-gray-600">{{ $txn['date']?->format('M d, Y') ?? '—' }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle font-semibold text-gray-900">{{ $txn['reference'] }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right tabular-nums text-gray-900">{{ format_number(abs($txn['amount'])) }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right tabular-nums text-gray-600">{{ format_number($txn['paid']) }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right font-bold tabular-nums {{ $txn['balance'] > 0 ? 'text-[#DC2626]' : 'text-gray-900' }}">{{ format_number(abs($txn['balance'])) }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-center">
                                                <span class="x-badge {{ $tStatus['class'] }}"><span class="x-badge-dot"></span>{{ str_replace('_', ' ', ucfirst($txn['status'])) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-6 text-center text-sm text-slate-400">{{ __('No transactions found.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                {{-- right rail --}}
                <aside class="x-rail-wrap">
                    <div class="x-rail">
                        <div class="x-rail-card">
                            <div class="x-rail-title">{{ __('Summary') }}</div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Opening Balance') }}</span><span class="x-rail-vv">{{ format_number($customer->opening_balance ?? 0) }}</span></div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Total Invoiced') }}</span><span class="x-rail-vv">{{ format_number($totalInvoiced) }}</span></div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Credit Limit') }}</span><span class="x-rail-vv">{{ format_number($customer->credit_limit ?? 0) }}</span></div>
                            <div class="x-rail-gt"><span class="x-rail-vl">{{ __('Open Balance') }}</span><span class="x-rail-vv">{{ format_number($balanceDue) }}</span></div>
                        </div>

                        <nav class="x-rail-card">
                            <div class="x-rail-title">{{ __('Quick Links') }}</div>
                            <div class="x-rail-nav">
                                <a href="{{ route('accounting.invoices.create', ['customer_id' => $customer->id]) }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg>
                                    {{ __('New Invoice') }}
                                </a>
                                <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $customer->id]) }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                    {{ __('Record Payment') }}
                                </a>
                                <a href="{{ route('accounting.reports.customer-statement', ['customer_id' => $customer->id]) }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
                                    {{ __('View Statement') }}
                                </a>
                                <a href="{{ route('accounting.customers.index') }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                    {{ __('All Customers') }}
                                </a>
                            </div>
                        </nav>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
