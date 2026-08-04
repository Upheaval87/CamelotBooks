<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('New Journal Entry') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.journal-entries.create') }}">
                    {{ __('New Journal Entry') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.journal-entries.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>Reversed</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_from" value="{{ __('Date From') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="{{ __('Date To') }}" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="request('branch_id')"
                            :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                            placeholder="{{ __('Search branches...') }}"
                        />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('status') || request('date_from') || request('date_to') || request('branch_id'))
                            <a href="{{ route('accounting.journal-entries.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                                <th>Journal #</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Debit ({{ $cs }})</th>
                                <th class="text-right">Credit ({{ $cs }})</th>
                                <th class="text-center">Status</th>
                                <th>Branch</th>
                                <th>Created By</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($journalEntries as $je)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.journal-entries.show', $je) }}" class="text-ink hover:text-gold">
                                            {{ $je->journal_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $je->date->format('M d, Y') }}
                                    </td>
                                    <td class="text-ink-soft max-w-xs truncate">
                                        {{ $je->memo ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($je->total_debit) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($je->total_credit) }}
                                    </td>
                                    <td class="text-center">
                                        @if($je->status === 'draft')
                                            <span class="status-pill neutral">Draft</span>
                                        @elseif($je->status === 'pending_approval')
                                            <span class="status-pill neutral">Pending Approval</span>
                                        @elseif($je->status === 'posted')
                                            <span class="status-pill positive">Posted</span>
                                        @elseif($je->status === 'reversed')
                                            <span class="status-pill negative">Reversed</span>
                                        @else
                                            <span class="status-pill neutral">{{ ucfirst($je->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $je->branch->name ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $je->createdBy->name ?? '—' }}
                                    </td>
                                    <td class="text-right">
                                        @if($je->status === 'draft')
                                            @can('journal-entries.submit')
                                                <form method="POST" action="{{ route('accounting.journal-entries.submit-for-approval', $je) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">Submit</button>
                                                </form>
                                            @endcan
                                        @endif
                                        @if($je->status === 'pending_approval')
                                            @can('journal-entries.approve')
                                                <form method="POST" action="{{ route('accounting.journal-entries.approve', $je) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                                </form>
                                            @endcan
                                        @endif
                                        @if($je->status === 'posted')
                                            @can('journal-entries.reverse')
                                                <form method="POST" action="{{ route('accounting.journal-entries.reverse', $je) }}" class="inline" onsubmit="return confirm('Are you sure you want to reverse this entry?');">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Reverse</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-ink-soft">
                                        No journal entries found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $journalEntries->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
