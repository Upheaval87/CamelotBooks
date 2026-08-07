<x-app-layout>
    <x-list-header title="{{ __('Create GRN') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.goods-received-notes.create') }}">
                    {{ __('Create GRN') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.goods-received-notes.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('status'))
                            <a href="{{ route('accounting.goods-received-notes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>GRN #</th>
                                <th>Date</th>
                                <th>PO #</th>
                                <th>Vendor</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notes as $grn)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.goods-received-notes.show', $grn) }}" class="text-ink hover:text-gold">{{ $grn->grn_number }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $grn->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $grn->purchaseOrder->po_number ?? '—' }}</td>
                                    <td>{{ $grn->vendor->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @switch($grn->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('posted')
                                                <span class="status-pill positive">Posted</span>
                                                @break
                                            @case('cancelled')
                                                <span class="status-pill negative">Cancelled</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.goods-received-notes.show', $grn) }}" class="text-ink hover:text-gold">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">No goods received notes found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($notes->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $notes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
