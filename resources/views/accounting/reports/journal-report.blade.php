<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Journal Report" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Entry #</th>
                <th>Reference</th>
                <th>Memo</th>
                <th>Account</th>
                <th class="text-right">Debit ({{ $cs }})</th>
                <th class="text-right">Credit ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($entries as $entry)
                    @foreach($entry->lines as $line)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm whitespace-nowrap">{{ $entry->date }}</td>
                        <td>{{ $entry->entry_number ?? $entry->id }}</td>
                        <td>{{ $entry->reference }}</td>
                        <td>{{ $entry->memo }}</td>
                        <td>{{ $line->account->code }} - {{ $line->account->name }}</td>
                        <td class="numeric">{{ $line->debit > 0 ? format_number($line->debit) : '' }}</td>
                        <td class="numeric">{{ $line->credit > 0 ? format_number($line->credit) : '' }}</td>
                    </tr>
                    @endforeach
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No journal entries found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm text-right">Totals</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_debit) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_credit) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Journal Report" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Entry #</th>
                <th>Reference</th>
                <th>Memo</th>
                <th>Account</th>
                <th class="text-right">Debit ({{ $cs }})</th>
                <th class="text-right">Credit ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($entries as $entry)
                    @foreach($entry->lines as $line)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm whitespace-nowrap">{{ $entry->date }}</td>
                        <td>{{ $entry->entry_number ?? $entry->id }}</td>
                        <td>{{ $entry->reference }}</td>
                        <td>{{ $entry->memo }}</td>
                        <td>{{ $line->account->code }} - {{ $line->account->name }}</td>
                        <td class="numeric">{{ $line->debit > 0 ? format_number($line->debit) : '' }}</td>
                        <td class="numeric">{{ $line->credit > 0 ? format_number($line->credit) : '' }}</td>
                    </tr>
                    @endforeach
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No journal entries found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm text-right">Totals</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_debit) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_credit) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>