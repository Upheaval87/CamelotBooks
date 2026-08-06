<x-app-layout>
    <x-slot name="header">{{ __('Branch Requests') }}</x-slot>

    @include('superadmin._nav', ['active' => 'branch-requests'])

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ $errors->first() }}</div>
            @endif

            <div class="list-table-wrap">
                <div class="overflow-x-auto">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Branch</th>
                                <th class="text-center">Qty</th>
                                <th>Requested</th>
                                <th>Quotation</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $r)
                                <tr>
                                    <td class="font-medium text-ink">{{ $r->company_name ?? ($r->company?->name ?? '—') }}</td>
                                    <td>
                                        {{ $r->branch_name }}
                                        @if($r->branch_code)
                                            <span class="block text-xs text-ink-soft">{{ $r->branch_code }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $r->requested_quantity }}</td>
                                    <td>{{ $r->requested_at?->format('M j, Y') }}</td>
                                    <td>
                                        @if($r->quotation)
                                            <span class="text-xs">{{ $r->quotation->quotation_number }}</span>
                                            <span class="block text-xs text-ink-soft">{{ number_format($r->quotation->total, 2) }} {{ $r->quotation->currency_code }}</span>
                                        @else
                                            <span class="text-ink-soft">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @include('branch-requests._status', ['status' => $r->status])
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('superadmin.companies.branch-requests.show', [$r->company_id, $r->id]) }}" class="btn-ghost px-3 py-1 text-xs">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-ink-soft text-center">No branch requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
