<x-app-layout>
    <x-list-header title="{{ __('Create Requisition') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.purchase-requisitions.create') }}">
                    {{ __('Create Requisition') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.purchase-requisitions.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('status'))
                            <a href="{{ route('accounting.purchase-requisitions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                                <th>Requisition #</th>
                                <th>Date</th>
                                <th>Requested By</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requisitions as $requisition)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.purchase-requisitions.show', $requisition) }}" class="text-ink hover:text-gold">
                                            {{ $requisition->requisition_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $requisition->date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td>
                                        {{ $requisition->createdBy->name ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @switch($requisition->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('submitted')
                                                <span class="status-pill neutral">Submitted</span>
                                                @break
                                            @case('approved')
                                                <span class="status-pill positive">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="status-pill negative">Rejected</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.purchase-requisitions.show', $requisition) }}" class="text-ink hover:text-gold">View</a>
                                        @if($requisition->status === 'draft')
                                            <a href="{{ route('accounting.purchase-requisitions.edit', $requisition) }}" class="text-ink hover:text-gold">Edit</a>
                                            @can('purchase-requisitions.submit')
                                                <form method="POST" action="{{ route('accounting.purchase-requisitions.submit', $requisition) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900 text-sm">Submit</button>
                                                </form>
                                            @endcan
                                        @endif
                                        @if($requisition->status === 'submitted')
                                            @can('purchase-requisitions.approve')
                                                <form method="POST" action="{{ route('accounting.purchase-requisitions.approve', $requisition) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900 text-sm">Approve</button>
                                                </form>
                                            @endcan
                                            @can('purchase-requisitions.reject')
                                                <form method="POST" action="{{ route('accounting.purchase-requisitions.reject', $requisition) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Reject</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">
                                        No purchase requisitions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($requisitions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $requisitions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
