@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Bills') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Bills" createRoute="{{ route('accounting.bills.create') }}" createLabel="Create Bill" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.bills.index') }}" searchPlaceholder="Vendor name..." entity="bill">
                        <select name="status" class="list-filter-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
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
                                    <th>Bill #</th>
                                    <th>Vendor</th>
                                    <th>Bill Date</th>
                                    <th>Due Date</th>
                                    <th class="text-right">Amount ({{ $cs }})</th>
                                    <th class="text-right">Paid ({{ $cs }})</th>
                                    <th class="text-right">Balance Due ({{ $cs }})</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bills as $bill)
                                <tr>
                                    <td><a href="{{ route('accounting.bills.show', $bill) }}">{{ $bill->bill_number }}</a></td>
                                    <td>{{ $bill->vendor->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $bill->bill_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $bill->due_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="list-numeric">{{ format_number($bill->total) }}</td>
                                    <td class="list-numeric">{{ format_number($bill->amount_paid) }}</td>
                                    <td class="list-numeric">{{ format_number($bill->balance_due) }}</td>
                                    <td class="text-center">
                                        @switch($bill->status)
                                            @case('draft')<span class="status-pill neutral">Draft</span>@break
                                            @case('pending')<span class="status-pill neutral">Pending</span>@break
                                            @case('approved')<span class="status-pill neutral">Approved</span>@break
                                            @case('paid')<span class="status-pill positive">Paid</span>@break
                                            @case('overdue')<span class="status-pill negative">Overdue</span>@break
                                            @case('void')<span class="status-pill neutral">Void</span>@break
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.bills.show', $bill) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            @if($bill->status === 'draft')
                                                <a href="{{ route('accounting.bills.edit', $bill) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span class="icon-btn-tooltip">Edit</span></a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-ink-soft py-8">No bills found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($bills->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $bills->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($bills as $bill)
                        <div class="list-mobile-card">
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Bill #</span><span class="list-mobile-card-value"><a href="{{ route('accounting.bills.show', $bill) }}">{{ $bill->bill_number }}</a></span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Vendor</span><span class="list-mobile-card-value">{{ $bill->vendor->name ?? '—' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Amount</span><span class="list-mobile-card-value">{{ format_number($bill->total) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Balance</span><span class="list-mobile-card-value">{{ format_number($bill->balance_due) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">
                                @php $s = $bill->status; @endphp
                                @if($s === 'draft')<span class="status-pill neutral">{{ __('Draft') }}</span>
                                @elseif($s === 'pending')<span class="status-pill neutral">{{ __('Pending') }}</span>
                                @elseif($s === 'approved')<span class="status-pill neutral">{{ __('Approved') }}</span>
                                @elseif($s === 'paid')<span class="status-pill positive">{{ __('Paid') }}</span>
                                @elseif($s === 'overdue')<span class="status-pill negative">{{ __('Overdue') }}</span>
                                @elseif($s === 'void')<span class="status-pill neutral">{{ __('Void') }}</span>
                                @else<span class="status-pill neutral">{{ $s }}</span>
                                @endif
                            </span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No bills found.</div>
                        @endforelse
                        @if($bills->hasPages())
                        <div class="px-2 py-3">{{ $bills->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Bills" :groups="[
                        [
                            ['route' => route('accounting.bills.index'), 'title' => 'All Bills', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['route' => route('accounting.bills.index', ['status' => 'draft']), 'title' => 'Drafts', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                            ['route' => route('accounting.bills.index', ['status' => 'pending']), 'title' => 'Pending Approval', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.bills.index', ['status' => 'paid']), 'title' => 'Paid', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.bills.index', ['status' => 'overdue']), 'title' => 'Overdue', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.bills.create'), 'title' => 'Create Bill', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'New bill'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Aging Report', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
