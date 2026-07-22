<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Credit Notes') }}
            </h2>
            <a href="{{ route('accounting.credit-notes.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create Credit Note') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.credit-notes.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Customer name..." />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="applied" {{ request('status') === 'applied' ? 'selected' : '' }}>Applied</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('accounting.credit-notes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Invoice</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($creditNotes as $creditNote)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $creditNote->credit_note_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $creditNote->customer->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $creditNote->credit_note_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $creditNote->invoice->invoice_number ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($creditNote->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($creditNote->amount_applied, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                        {{ number_format($creditNote->available, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @switch($creditNote->status)
                                            @case('draft')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                                                @break
                                            @case('issued')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Issued</span>
                                                @break
                                            @case('applied')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Applied</span>
                                                @break
                                            @case('void')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">Void</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No credit notes found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($creditNotes->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $creditNotes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
