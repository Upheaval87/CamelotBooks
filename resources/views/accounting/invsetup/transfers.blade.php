@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
@endphp
<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Transfers & Adjustments') }}</h1>
                <div class="inv-sub">{{ __('Track movement between warehouses and adjustment history.') }}</div>
            </div>
            <div style="display:flex;gap:10px">
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button">{{ __('Export CSV') }}</button>
                <a href="{{ route('accounting.invsetup.transfers.create') }}" class="inv-btn inv-btn-ghost inv-btn-sm" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('＋ New Transfer') }}</a>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'transfers'])

        <div class="inv-kpis">
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Total Transfers') }}</div>
                <div class="inv-kpi-v">{{ $transfers->total() }}</div>
                <div class="inv-kpi-n">{{ __('this month') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('In Transit') }}</div>
                <div class="inv-kpi-v">{{ $statusCounts['in_transit'] ?? 0 }}</div>
                <div class="inv-kpi-n">{{ __('awaiting receipt') }}</div>
            </div>
            <div class="inv-kpi">
                <div class="inv-kpi-l">{{ __('Adjustments') }}</div>
                <div class="inv-kpi-v">{{ isset($adjustments) ? $adjustments->total() : 0 }}</div>
                <div class="inv-kpi-n inv-kpi-n-warn">{{ __('write-downs') }}</div>
            </div>
            <div class="inv-kpi hero">
                <div class="inv-kpi-l">{{ __('Variance') }}</div>
                <div class="inv-kpi-v">{{ $cs }}0.00</div>
                <div class="inv-kpi-n">{{ __('net adjustment value') }}</div>
            </div>
        </div>

        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 105.64-12.36L1 10"/></svg>
                </div>
                <h2>{{ __('Transfer & Adjustment History') }}</h2>
                <span class="inv-rule"></span>
                <div class="right" style="margin-left:auto">
                    <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button">{{ __('Filter') }}</button>
                </div>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Ref') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('From → To') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('Value') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                        <tr>
                            <td class="inv-mono">{{ $transfer->transfer_number ?? 'TR-' . str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="em">{{ $transfer->created_at->format('d M Y') }}</td>
                            <td><span class="inv-badge inv-badge-teal"><span class="inv-badge-dot"></span>{{ __('Transfer') }}</span></td>
                            <td>{{ $transfer->from_branch_id ? ($transfer->fromBranch?->name ?? '—') : '—' }} → {{ $transfer->to_branch_id ? ($transfer->toBranch?->name ?? '—') : '—' }}</td>
                            <td class="num">{{ $transfer->lines_count ?? 0 }}</td>
                            <td class="num">{{ $cs }}{{ number_format($transfer->total_value ?? 0, 2) }}</td>
                            <td>
                                @if($transfer->status === 'in_transit')
                                <span class="inv-badge inv-badge-warning"><span class="inv-badge-dot"></span>{{ __('In Transit') }}</span>
                                @elseif($transfer->status === 'completed')
                                <span class="inv-badge inv-badge-active"><span class="inv-badge-dot"></span>{{ __('Posted') }}</span>
                                @else
                                <span class="inv-badge inv-badge-info"><span class="inv-badge-dot"></span>{{ ucfirst($transfer->status) }}</span>
                                @endif
                            </td>
                            <td class="inv-row-act">
                                <button class="inv-ibtn" title="{{ __('View') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 105.64-12.36L1 10"/></svg>
                                    </div>
                                    <p>{{ __('No recent transfers or adjustments.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Transfers and adjustments will appear here.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transfers->hasPages())
            <div style="padding:16px 20px">{{ $transfers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
