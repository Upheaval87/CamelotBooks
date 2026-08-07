<x-app-layout>
    <x-list-header title="{{ __('POS Z-Report') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(!$data)
                <div class="card p-6 text-center text-ink-soft">
                    No closed till sessions found.
                </div>
                @return
            @endif

            <div class="card p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-ink-soft uppercase">Terminal</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->terminal?->identifier ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-soft uppercase">Cashier</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->user?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-soft uppercase">Opened</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->opened_at?->format('M d, H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-soft uppercase">Closed</p>
                        <p class="font-semibold text-gray-900">{{ $data['session']->closed_at?->format('M d, H:i') ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="card p-6 mb-6">
                <div class="form-section-label">1 · Sales Summary</div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $data['sales_count'] }}</p>
                        <p class="text-xs text-ink-soft uppercase">Gross Sales</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-red-600">{{ $data['returns_count'] }}</p>
                        <p class="text-xs text-ink-soft uppercase">Returns</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">@money($data['sales_total'])</p>
                        <p class="text-xs text-ink-soft uppercase">Gross Amount</p>
                    </div>
                    <div class="text-center p-4 bg-gold-soft rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">@money($data['net_sales'])</p>
                        <p class="text-xs text-ink-soft uppercase">Net Sales</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 mt-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">Subtotal</p>
                        <p class="font-semibold text-gray-900">@money($data['sales_subtotal'])</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">Tax</p>
                        <p class="font-semibold text-gray-900">@money($data['sales_tax'])</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-lg">
                        <p class="text-sm text-gray-600">Returns</p>
                        <p class="font-semibold text-red-600">-@money($data['returns_total'])</p>
                    </div>
                </div>
            </div>

            <div class="card p-6 mb-6">
                <div class="form-section-label">2 · Payments by Method</div>
                <table class="datasheet">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th class="text-right">Sales</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['payments_by_method'] as $pm)
                            <tr>
                                <td>{{ $pm->method_name }}</td>
                                <td class="numeric">{{ $pm->sale_count }}</td>
                                <td class="numeric font-semibold">@money($pm->total_amount)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-ink-soft text-center">No payments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-gold-soft border border-gold-line card p-6 mb-6">
                <div class="form-section-label">3 · Cash Drawer Reconciliation</div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gold-700">Opening Float</p>
                        <p class="font-semibold text-gray-900">@money($data['opening_float'])</p>
                    </div>
                    <div>
                        <p class="text-sm text-gold-700">+ Cash Payments</p>
                        <p class="font-semibold text-gray-900">@money($data['cash_payments'])</p>
                    </div>
                    <div>
                        <p class="text-sm text-gold-700">− Returns (Cash)</p>
                        <p class="font-semibold text-gray-900">@money($data['returns_total'])</p>
                    </div>
                    <div>
                        <p class="text-sm text-gold-700">= Expected Cash</p>
                        <p class="text-xl font-bold text-gray-900">@money($data['expected_cash'])</p>
                    </div>
                    <div>
                        <p class="text-sm text-gold-700">Actual Cash Count</p>
                        <p class="text-xl font-bold text-gray-900">{{ $data['actual_cash_count'] !== null ? format_money($data['actual_cash_count']) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gold-700">Variance</p>
                        <p class="text-xl font-bold {{ ($data['variance'] ?? 0) > 0 ? 'text-green-600' : (($data['variance'] ?? 0) < 0 ? 'text-red-600' : 'text-gray-900') }}">
                            {{ $data['variance'] !== null ? ($data['variance'] >= 0 ? '+' : '') . format_money($data['variance']) : '—' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
