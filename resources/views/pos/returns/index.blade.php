<x-app-layout>
    <x-list-header title="{{ __('POS Returns / Refunds') }}" />

    <div class="flex justify-end mb-4 px-4 sm:px-0">
        <x-button variant="primary" href="{{ route('pos.returns.create') }}">{{ __('New Return') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Return #</th>
                                <th>Original Sale</th>
                                <th>Date</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($returns as $return)
                                <tr>
                                    <td>{{ $return->return_number }}</td>
                                    <td>{{ $return->sale?->sale_number ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $return->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numeric text-red-600 font-semibold">-@money($return->total)</td>
                                    <td class="text-center">
                                        @if($return->isPosted())
                                            <span class="status-pill positive">Posted</span>
                                        @elseif($return->isDraft())
                                            <span class="status-pill negative">Draft</span>
                                        @else
                                            <span class="status-pill negative">Voided</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('pos.returns.show', $return) }}" class="text-gold-700 hover:text-gold-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-ink-soft text-center">No returns found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3">{{ $returns->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
