<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Purchase Order Status</h1>
    @forelse($purchaseOrders as $po)
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-900">PO #{{ $po->po_number }}</span>
                <span class="ml-4 text-sm text-gray-500">Date: {{ $po->date }}</span>
                <span class="ml-4 text-sm text-gray-500">Vendor: {{ $po->vendor->name ?? 'N/A' }}</span>
            </div>
            <div>
                @if($po->status === 'received')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Received</span>
                @elseif($po->status === 'partial')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($po->status) }}</span>
                @endif
            </div>
        </div>
        <table class="datasheet">
            <thead class="bg-white"><tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-right">Ordered</th>
                <th class="text-right">Received</th>
                <th class="text-right">Remaining</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($po->lines as $line)
                <tr class="hover:bg-gray-50">
                    <td>{{ $line->product->name ?? 'N/A' }}</td>
                    <td>{{ $line->product->sku ?? 'N/A' }}</td>
                    <td class="numeric">{{ format_number($line->qty_ordered) }}</td>
                    <td class="numeric">{{ format_number($line->qty_received) }}</td>
                    <td class="numeric">{{ format_number($line->qty_ordered - $line->qty_received) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
        <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
            <p class="text-sm text-gray-500">No purchase orders found.</p>
        </div>
    @endforelse
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Purchase Order Status</h1>
    @forelse($purchaseOrders as $po)
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-semibold text-gray-900">PO #{{ $po->po_number }}</span>
                <span class="ml-4 text-sm text-gray-500">Date: {{ $po->date }}</span>
                <span class="ml-4 text-sm text-gray-500">Vendor: {{ $po->vendor->name ?? 'N/A' }}</span>
            </div>
            <div>
                @if($po->status === 'received')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Received</span>
                @elseif($po->status === 'partial')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($po->status) }}</span>
                @endif
            </div>
        </div>
        <table class="datasheet">
            <thead class="bg-white"><tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-right">Ordered</th>
                <th class="text-right">Received</th>
                <th class="text-right">Remaining</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($po->lines as $line)
                <tr class="hover:bg-gray-50">
                    <td>{{ $line->product->name ?? 'N/A' }}</td>
                    <td>{{ $line->product->sku ?? 'N/A' }}</td>
                    <td class="numeric">{{ format_number($line->qty_ordered) }}</td>
                    <td class="numeric">{{ format_number($line->qty_received) }}</td>
                    <td class="numeric">{{ format_number($line->qty_ordered - $line->qty_received) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
        <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
            <p class="text-sm text-gray-500">No purchase orders found.</p>
        </div>
    @endforelse
</div>
</x-app-layout>