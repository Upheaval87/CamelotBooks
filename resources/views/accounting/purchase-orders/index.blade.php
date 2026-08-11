<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $statusFilter = request('status', '');
        $poStats = [
            'total' => $orders->total(),
            'draft' => $stats['draft'] ?? 0,
            'sent' => $stats['sent'] ?? 0,
            'partially_received' => $stats['partially_received'] ?? 0,
            'fully_received' => $stats['fully_received'] ?? 0,
            'cancelled' => $stats['cancelled'] ?? 0,
        ];
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="page-head">
                <div>
                    <h1>{{ __('Purchase Orders') }}</h1>
                    <div class="sub">{{ __('Order goods and services from your vendors.') }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.purchase-orders.create') }}" class="btn btn-cta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v16m8-8H4"/></svg>
                        {{ __('Create Purchase Order') }}
                    </a>
                </div>
            </div>

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- stats --}}
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18M3 4v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V4M3 4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2M8 8h8M8 12h8M8 16h5" /></svg></span>
                            <h2>{{ __('Purchase Orders') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="statgrid statgrid--6">
                            <a href="{{ route('accounting.purchase-orders.index') }}" class="fbox {{ $statusFilter === '' ? 'on' : '' }}" style="text-decoration:none">
                                <span class="t" style="background:var(--deep-1,#17565D)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 12h6m-6 4h6M9 8h6M7 4h10a2 2 0 0 1 2 2v16H5V6a2 2 0 0 1 2-2z"/></svg></span>
                                <div><div class="l">{{ __('Total') }}</div><div class="v">{{ $poStats['total'] }}</div></div>
                            </a>
                            <a href="{{ route('accounting.purchase-orders.index', ['status' => 'draft']) }}" class="fbox {{ $statusFilter === 'draft' ? 'on' : '' }}" style="text-decoration:none">
                                <span class="t" style="background:#8AA5A7"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>
                                <div><div class="l">{{ __('Draft') }}</div><div class="v">{{ $poStats['draft'] }}</div></div>
                            </a>
                            <a href="{{ route('accounting.purchase-orders.index', ['status' => 'sent']) }}" class="fbox {{ $statusFilter === 'sent' ? 'on' : '' }}" style="text-decoration:none">
                                <span class="t" style="background:#128F8E"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></span>
                                <div><div class="l">{{ __('Sent') }}</div><div class="v">{{ $poStats['sent'] }}</div></div>
                            </a>
                            <a href="{{ route('accounting.purchase-orders.index', ['status' => 'partially_received']) }}" class="fbox {{ $statusFilter === 'partially_received' ? 'on' : '' }}" style="text-decoration:none">
                                <span class="t" style="background:#7FD1C0;color:#11454b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v6m0 6v6M3 12h6m6 0h6"/></svg></span>
                                <div><div class="l">{{ __('Partial') }}</div><div class="v">{{ $poStats['partially_received'] }}</div></div>
                            </a>
                            <a href="{{ route('accounting.purchase-orders.index', ['status' => 'fully_received']) }}" class="fbox {{ $statusFilter === 'fully_received' ? 'on' : '' }}" style="text-decoration:none">
                                <span class="t" style="background:#22c55e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                <div><div class="l">{{ __('Received') }}</div><div class="v">{{ $poStats['fully_received'] }}</div></div>
                            </a>
                            <a href="{{ route('accounting.purchase-orders.index', ['status' => 'cancelled']) }}" class="fbox {{ $statusFilter === 'cancelled' ? 'on' : '' }}" style="text-decoration:none">
                                <span class="t" style="background:#B91C1C"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></span>
                                <div><div class="l">{{ __('Cancelled') }}</div><div class="v">{{ $poStats['cancelled'] }}</div></div>
                            </a>
                        </div>

                        <form method="GET" action="{{ route('accounting.purchase-orders.index') }}" class="controls">
                            <select name="status" class="input" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ $statusFilter === 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="partially_received" {{ $statusFilter === 'partially_received' ? 'selected' : '' }}>Partially Received</option>
                                <option value="fully_received" {{ $statusFilter === 'fully_received' ? 'selected' : '' }}>Fully Received</option>
                                <option value="closed" {{ $statusFilter === 'closed' ? 'selected' : '' }}>Closed</option>
                                <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            <button type="submit" class="btn btn-ghost">{{ __('Filter') }}</button>
                            @if($statusFilter)
                                <a href="{{ route('accounting.purchase-orders.index') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                            @endif
                            <span class="chip-t">{{ $poStats['total'] }} {{ __('total') }}</span>
                        </form>
                    </section>

                    {{-- list --}}
                    <section class="card card-sec">
                        <div class="li-wrap">
                            <table>
                                <thead><tr>
                                    <th style="width:16%">{{ __('PO #') }}</th>
                                    <th style="width:12%">{{ __('Date') }}</th>
                                    <th style="width:24%">{{ __('Vendor') }}</th>
                                    <th class="num" style="width:14%">{{ __('Total') }} ({{ $cs }})</th>
                                    <th style="width:16%">{{ __('Status') }}</th>
                                    <th class="num" style="width:18%">{{ __('Actions') }}</th>
                                </tr></thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php $total = $order->lines->sum('amount'); @endphp
                                        <tr>
                                            <td><a href="{{ route('accounting.purchase-orders.show', $order) }}" class="link" style="font-family:ui-monospace,Menlo,monospace;font-size:12px">{{ $order->po_number }}</a></td>
                                            <td class="em">{{ $order->date?->format('M d, Y') ?? '—' }}</td>
                                            <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $order->vendor->name ?? '—' }}</td>
                                            <td class="numr">{{ format_number($total) }}</td>
                                            <td>
                                                @switch($order->status)
                                                    @case('draft') <span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span> @break
                                                    @case('sent') <span class="badge b-teal"><span class="bdot"></span>{{ __('Sent') }}</span> @break
                                                    @case('partially_received') <span class="badge b-mint"><span class="bdot"></span>{{ __('Partial') }}</span> @break
                                                    @case('fully_received') <span class="badge b-post"><span class="bdot"></span>{{ __('Received') }}</span> @break
                                                    @case('closed') <span class="badge b-gray"><span class="bdot"></span>{{ __('Closed') }}</span> @break
                                                    @case('cancelled') <span class="badge b-red"><span class="bdot"></span>{{ __('Cancelled') }}</span> @break
                                                @endswitch
                                            </td>
                                            <td class="num">
                                                <span style="display:inline-flex;gap:6px;justify-content:flex-end">
                                                    <a href="{{ route('accounting.purchase-orders.show', $order) }}" class="ibtn" title="{{ __('View') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                                    @if($order->status === 'draft')
                                                        <a href="{{ route('accounting.purchase-orders.edit', $order) }}" class="ibtn" title="{{ __('Edit') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="empty">{{ __('No purchase orders found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                            <div class="pagi">
                                <span class="t">{{ __('Showing') }} {{ $orders->firstItem() }}–{{ $orders->lastItem() }} {{ __('of') }} {{ $orders->total() }} {{ __('purchase orders') }}</span>
                                <span class="pg">
                                    @if($orders->onFirstPage())
                                        <span class="pgbtn is-disabled" aria-hidden="true">‹</span>
                                    @else
                                        <a href="{{ $orders->previousPageUrl() }}" rel="prev" aria-label="Previous">‹</a>
                                    @endif
                                    @foreach($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                                        @if($page == $orders->currentPage())
                                            <span class="pgbtn cur">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                    @if($orders->hasMorePages())
                                        <a href="{{ $orders->nextPageUrl() }}" rel="next" aria-label="Next">›</a>
                                    @else
                                        <span class="pgbtn is-disabled" aria-hidden="true">›</span>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18M3 4v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V4M3 4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2M8 8h8M8 12h8M8 16h5"/></svg></span><h2>{{ __('Views') }}</h2></div>
                            <div class="vlist">
                                <a href="{{ route('accounting.purchase-orders.index') }}" class="vitem {{ $statusFilter === '' ? 'on' : '' }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 12h6m-6 4h6M9 8h6M7 4h10a2 2 0 0 1 2 2v16H5V6a2 2 0 0 1 2-2z"/></svg></span>{{ __('All') }}</a>
                                <a href="{{ route('accounting.purchase-orders.index', ['status' => 'draft']) }}" class="vitem {{ $statusFilter === 'draft' ? 'on' : '' }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>{{ __('Draft') }}</a>
                                <a href="{{ route('accounting.purchase-orders.index', ['status' => 'sent']) }}" class="vitem {{ $statusFilter === 'sent' ? 'on' : '' }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></span>{{ __('Sent') }}</a>
                                <a href="{{ route('accounting.purchase-orders.index', ['status' => 'fully_received']) }}" class="vitem {{ $statusFilter === 'fully_received' ? 'on' : '' }}"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>{{ __('Received') }}</a>
                            </div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Reports') }}</h2></div>
                            <div class="vlist">
                                <a href="{{ route('accounting.general-ledger.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>{{ __('General Ledger') }}</a>
                                <a href="{{ route('accounting.trial-balance.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M8 17l4-4 3 3 5-6"/></svg></span>{{ __('Trial Balance') }}</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
