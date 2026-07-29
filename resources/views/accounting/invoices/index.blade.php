<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Invoices') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.invoices.create') }}">
                    {{ __('Create Invoice') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.invoices.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Customer name..." />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('accounting.invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
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
                                    <td>
                                        <a href="{{ route('accounting.invoices.show', $invoice) }}" class="text-ink hover:text-gold">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $invoice->customer->name ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($invoice->total) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($invoice->amount_paid) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($invoice->balance_due) }}
                                    </td>
                                    <td class="text-center">
                                        @switch($invoice->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('sent')
                                                <span class="status-pill neutral">Sent</span>
                                                @break
                                            @case('paid')
                                                <span class="status-pill positive">Paid</span>
                                                @break
                                            @case('overdue')
                                                <span class="status-pill negative">Overdue</span>
                                                @break
                                            @case('void')
                                                <span class="status-pill neutral">Void</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.invoices.show', $invoice) }}" class="text-ink hover:text-gold">View</a>
                                        @if($invoice->status === 'draft')
                                            <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="text-ink hover:text-gold">Edit</a>
                                            <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">Post</button>
                                            </form>
                                        @endif
                                        @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                                            <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to void this invoice?')">Void</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-ink-soft">
                                        No invoices found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
