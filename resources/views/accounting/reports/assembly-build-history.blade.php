<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Assembly Build History" />
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    <option value="">All Types</option>
                    <option value="build" {{ request('type') === 'build' ? 'selected' : '' }}>Build</option>
                    <option value="unbuild" {{ request('type') === 'unbuild' ? 'selected' : '' }}>Unbuild</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <x-primary-button type="submit">Filter</x-primary-button>
        </form>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Build #</th>
                <th>Type</th>
                <th>Assembly Product</th>
                <th>BOM</th>
                <th>Date</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Cost ({{ $cs }})</th>
                <th class="text-right">Total ({{ $cs }})</th>
                <th>Status</th>
                <th>Created By</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($builds as $b)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-sans">{{ $b['build_number'] }}</td>
                    <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $b['type'] === 'build' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">{{ ucfirst($b['type']) }}</span></td>
                    <td>{{ $b['assembly_product'] }}</td>
                    <td class="text-ink-soft">{{ $b['bom_name'] ?? '—' }}</td>
                    <td>{{ $b['date'] }}</td>
                    <td class="numeric">{{ format_number($b['quantity']) }}</td>
                    <td class="numeric">{{ format_number($b['unit_cost']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($b['total_component_cost']) }}</td>
                    <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $b['status'] === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ ucfirst($b['status']) }}</span></td>
                    <td class="text-ink-soft">{{ $b['created_by'] }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500">No assembly builds found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
