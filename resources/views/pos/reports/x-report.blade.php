<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('POS X-Report') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(!$data)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    No till sessions found. Open a till session first.
                </div>
                @return
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Terminal</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->terminal?->identifier ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Cashier</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->user?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Opened</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->opened_at?->format('M d, H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Status</p>
                        @if($data['session']->isOpen())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Open</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Closed</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Sales Summary') }}</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $data['sales_count'] }}</p>
                        <p class="text-xs text-gray-500 uppercase">Sales Count</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($data['sales_total'], 2) }}</p>
                        <p class="text-xs text-gray-500 uppercase">Gross Sales</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">${{ number_format($data['returns_total'], 2) }}</p>
                        <p class="text-xs text-gray-500 uppercase">Returns</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">Subtotal</p>
                        <p class="font-semibold text-gray-900">${{ number_format($data['sales_subtotal'], 2) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">Tax</p>
                        <p class="font-semibold text-gray-900">${{ number_format($data['sales_tax'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payments by Method') }}</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sales</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($data['payments_by_method'] as $pm)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $pm->method_name }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-900">{{ $pm->sale_count }}</td>
                                <td class="px-4 py-2 text-sm text-right font-semibold text-gray-900">${{ number_format($pm->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-center text-sm text-gray-500">No payments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-indigo-50 border border-indigo-200 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-indigo-800 mb-4">{{ __('Cash Drawer') }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-indigo-600">Opening Float</p>
                        <p class="text-lg font-bold text-gray-900">${{ number_format($data['opening_float'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-indigo-600">+ Cash Payments</p>
                        <p class="text-lg font-bold text-gray-900">${{ number_format($data['cash_payments'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-indigo-600">− Returns (Cash)</p>
                        <p class="text-lg font-bold text-gray-900">${{ number_format($data['returns_total'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-indigo-600">= Expected Cash</p>
                        <p class="text-xl font-bold text-indigo-700">${{ number_format($data['expected_cash'], 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
