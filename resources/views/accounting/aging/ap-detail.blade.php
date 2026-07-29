<x-app-layout>
    <x-slot name="header">{{ __('A/P Aging Detail') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ap'])) }}">{{ __('Export CSV') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.aging.ap-detail') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$as_of_date" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.aging.ap-detail') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-4 flex gap-2">
                <a href="{{ route('accounting.aging.ap-summary', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Summary</a>
                <a href="{{ route('accounting.aging.ap-detail', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm">Detail</a>
            </div>

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">A/P Aging Detail as of {{ \Carbon\Carbon::parse($as_of_date)->format('M d, Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Bill #</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th class="text-right">Amount Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendors as $row)
                                <tr class="hover:bg-gray-50">
                                    <td>{{ $row['vendor_name'] }}</td>
                                    <td>{{ $row['bill_number'] ?? '-' }}</td>
                                    <td>{{ $row['due_date'] ?? '-' }}</td>
                                    <td>{{ $row['days_overdue'] ?? 0 }}</td>
                                    <td class="numeric">{{ format_money($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">No outstanding bills found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-3 text-sm font-bold text-gray-900 text-right">Total</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>