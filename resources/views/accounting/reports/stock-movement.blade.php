<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Stock Movement</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end flex-wrap">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">Product</label><input type="text" name="product_id" value="{{ request('product_id') }}" placeholder="Product ID or name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">Branch</label><input type="text" name="branch_id" value="{{ request('branch_id') }}" placeholder="Branch ID" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Running Qty</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($movements as $movement)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm whitespace-nowrap">{{ $movement->date }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->product->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->product->sku ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->type }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->reference }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($movement->qty) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($movement->unit_cost) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($movement->running_qty) }}</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No stock movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($reconciliation))
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Reconciliation</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Opening Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ format_number($reconciliation['opening']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Net Movement</p>
                <p class="text-lg font-bold text-gray-900">{{ format_number($reconciliation['net_movement']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Closing Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ format_number($reconciliation['closing']) }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Stock Movement</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end flex-wrap">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">Product</label><input type="text" name="product_id" value="{{ request('product_id') }}" placeholder="Product ID or name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">Branch</label><input type="text" name="branch_id" value="{{ request('branch_id') }}" placeholder="Branch ID" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Running Qty</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($movements as $movement)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm whitespace-nowrap">{{ $movement->date }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->product->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->product->sku ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->type }}</td>
                    <td class="px-4 py-2 text-sm">{{ $movement->reference }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($movement->qty) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($movement->unit_cost) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($movement->running_qty) }}</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No stock movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($reconciliation))
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Reconciliation</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Opening Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ format_number($reconciliation['opening']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Net Movement</p>
                <p class="text-lg font-bold text-gray-900">{{ format_number($reconciliation['net_movement']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Closing Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ format_number($reconciliation['closing']) }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
</x-app-layout>