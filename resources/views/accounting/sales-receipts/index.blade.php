@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Sales Receipts') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Sales Receipts" createRoute="{{ route('accounting.sales-receipts.create') }}" createLabel="Create Sales Receipt" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.sales-receipts.index') }}" searchPlaceholder="Customer, number..." entity="sales-receipt">
                        <select name="status" class="list-filter-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                    </x-list-filter-bar>

                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
                    @endif

                    <div class="list-table-wrap">
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th class="text-right">Total ({{ $cs }})</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receipts as $receipt)
                                <tr>
                                    <td><a href="{{ route('accounting.sales-receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></td>
                                    <td>{{ $receipt->customer->name ?? 'Walk-in' }}</td>
                                    <td class="text-ink-soft">{{ $receipt->receipt_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="list-numeric">{{ format_number($receipt->total) }}</td>
                                    <td class="text-center">
                                        @switch($receipt->status)
                                            @case('draft')<span class="status-pill neutral">Draft</span>@break
                                            @case('posted')<span class="status-pill positive">Posted</span>@break
                                            @case('voided')<span class="status-pill negative">Voided</span>@break
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.sales-receipts.show', $receipt) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            @if($receipt->status === 'draft')
                                                @can('sales-receipts.post')
                                                <form method="POST" action="{{ route('accounting.sales-receipts.post', $receipt) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span class="icon-btn-tooltip">Post</span></button>
                                                </form>
                                                @endcan
                                            @endif
                                            @if($receipt->status === 'posted')
                                                @can('sales-receipts.void')
                                                <form method="POST" action="{{ route('accounting.sales-receipts.void', $receipt) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="icon-btn" onclick="return confirm('Void this receipt?')"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg><span class="icon-btn-tooltip">Void</span></button>
                                                </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-ink-soft py-8">No sales receipts found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($receipts->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $receipts->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($receipts as $receipt)
                        <div class="list-mobile-card">
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Receipt #</span><span class="list-mobile-card-value"><a href="{{ route('accounting.sales-receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Customer</span><span class="list-mobile-card-value">{{ $receipt->customer->name ?? 'Walk-in' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Total</span><span class="list-mobile-card-value">{{ format_number($receipt->total) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">
                                @php $s = $receipt->status; @endphp
                                @if($s === 'draft')<span class="status-pill neutral">{{ __('Draft') }}</span>
                                @elseif($s === 'posted')<span class="status-pill positive">{{ __('Posted') }}</span>
                                @elseif($s === 'voided')<span class="status-pill negative">{{ __('Voided') }}</span>
                                @else<span class="status-pill neutral">{{ $s }}</span>
                                @endif
                            </span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No sales receipts found.</div>
                        @endforelse
                        @if($receipts->hasPages())
                        <div class="px-2 py-3">{{ $receipts->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Sales Receipts" :groups="[
                        [
                            ['route' => route('accounting.sales-receipts.index'), 'title' => 'All Receipts', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['route' => route('accounting.sales-receipts.index', ['status' => 'draft']), 'title' => 'Drafts', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                            ['route' => route('accounting.sales-receipts.index', ['status' => 'posted']), 'title' => 'Posted', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.sales-receipts.index', ['status' => 'voided']), 'title' => 'Voided', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.sales-receipts.create'), 'title' => 'Create Receipt', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'New receipt'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Daily Summary', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
