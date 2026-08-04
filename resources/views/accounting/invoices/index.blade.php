@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<x-app-layout>
    

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Invoices" createRoute="{{ route('accounting.invoices.create') }}" createLabel="Create Invoice" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.invoices.index') }}" searchPlaceholder="Customer name..." entity="invoice">
                        <select name="status" class="list-filter-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
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
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th class="text-right">Amount ({{ $cs }})</th>
                                    <th class="text-right">Paid ({{ $cs }})</th>
                                    <th class="text-right">Balance Due ({{ $cs }})</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('accounting.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->customer->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="list-numeric">{{ format_number($invoice->total) }}</td>
                                    <td class="list-numeric">{{ format_number($invoice->amount_paid) }}</td>
                                    <td class="list-numeric">{{ format_number($invoice->balance_due) }}</td>
                                    <td class="text-center">
                                        @switch($invoice->status)
                                            @case('draft')<span class="status-pill neutral">Draft</span>@break
                                            @case('sent')<span class="status-pill neutral">Sent</span>@break
                                            @case('paid')<span class="status-pill positive">Paid</span>@break
                                            @case('overdue')<span class="status-pill negative">Overdue</span>@break
                                            @case('void')<span class="status-pill neutral">Void</span>@break
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.invoices.show', $invoice) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            @if($invoice->status === 'draft')
                                                <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span class="icon-btn-tooltip">Edit</span></a>
                                                @can('invoices.post')
                                                <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span class="icon-btn-tooltip">Post</span></button>
                                                </form>
                                                @endcan
                                            @endif
                                            @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                                                @can('invoices.void')
                                                <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="icon-btn" onclick="return confirm('Are you sure you want to void this invoice?')"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg><span class="icon-btn-tooltip">Void</span></button>
                                                </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-ink-soft py-8">No invoices found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($invoices->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $invoices->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($invoices as $invoice)
                        <div class="list-mobile-card">
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Invoice #</span><span class="list-mobile-card-value"><a href="{{ route('accounting.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Customer</span><span class="list-mobile-card-value">{{ $invoice->customer->name ?? '—' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Amount</span><span class="list-mobile-card-value">{{ format_number($invoice->total) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Balance Due</span><span class="list-mobile-card-value">{{ format_number($invoice->balance_due) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">
                                @php $s = $invoice->status; @endphp
                                @if($s === 'draft')<span class="status-pill neutral">{{ __('Draft') }}</span>
                                @elseif($s === 'sent')<span class="status-pill neutral">{{ __('Sent') }}</span>
                                @elseif($s === 'paid')<span class="status-pill positive">{{ __('Paid') }}</span>
                                @elseif($s === 'overdue')<span class="status-pill negative">{{ __('Overdue') }}</span>
                                @elseif($s === 'void')<span class="status-pill neutral">{{ __('Void') }}</span>
                                @else<span class="status-pill neutral">{{ $s }}</span>
                                @endif
                            </span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No invoices found.</div>
                        @endforelse
                        @if($invoices->hasPages())
                        <div class="px-2 py-3">{{ $invoices->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Invoices" :groups="[
                        [
                            ['route' => route('accounting.invoices.index'), 'title' => 'All Invoices', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['route' => route('accounting.invoices.index', ['status' => 'draft']), 'title' => 'Drafts', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                            ['route' => route('accounting.invoices.index', ['status' => 'sent']), 'title' => 'Sent', 'icon' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8'],
                            ['route' => route('accounting.invoices.index', ['status' => 'paid']), 'title' => 'Paid', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.invoices.index', ['status' => 'overdue']), 'title' => 'Overdue', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.invoices.create'), 'title' => 'Create Invoice', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'New invoice'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Aging Report', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['route' => '#', 'title' => 'Sales Summary', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
