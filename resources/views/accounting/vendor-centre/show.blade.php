<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $companyId = session('current_company_id');
        $featPurchasing = \App\Services\FeatureManagement::isEnabled($companyId, 'purchasing');
    @endphp
    <x-slot name="header">{{ __('Vendor Centre') }} — {{ $vendor->name }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('Open Balance') }} ({{ $cs }})</p>
                    <p class="kpi-value text-brick">{{ format_number($stats['open_balance']) }}</p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('Total Bills') }} ({{ $cs }})</p>
                    <p class="kpi-value">{{ format_number($stats['total_bills']) }}</p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('Total Paid') }} ({{ $cs }})</p>
                    <p class="kpi-value text-forest">{{ format_number($stats['total_paid']) }}</p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('Credit Balance') }} ({{ $cs }})</p>
                    <p class="kpi-value {{ $stats['credit_balance'] > 0 ? 'text-forest' : '' }}">{{ format_number($stats['credit_balance']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('Total Expenses') }} ({{ $cs }})</p>
                    <p class="kpi-value">{{ format_number($stats['total_expenses']) }}</p>
                </div>
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('Bill Count') }}</p>
                    <p class="kpi-value">{{ $stats['bill_count'] }}</p>
                </div>
                @if($featPurchasing)
                <div class="kpi-card">
                    <p class="kpi-label">{{ __('PO Count') }}</p>
                    <p class="kpi-value">{{ $stats['po_count'] }}</p>
                </div>
                @endif
            </div>

            {{-- Vendor Info --}}
            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Vendor Details') }}</p>
                <div class="detail-grid">
                    <x-detail-field :label="__('Email')" :value="$vendor->email ?? '—'" />
                    <x-detail-field :label="__('Phone')" :value="$vendor->phone ?? '—'" />
                    <x-detail-field :label="__('Currency')" :value="$vendor->currency ?? 'USD'" />
                    <x-detail-field :label="__('Payment Terms')" :value="$vendor->payment_terms ?? '—'" />
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Quick Actions') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('accounting.bills.create') }}?vendor_id={{ $vendor->id }}" class="x-button x-button-ghost">{{ __('Create Bill') }}</a>
                    <a href="{{ route('accounting.vendor-payments.create') }}?vendor_id={{ $vendor->id }}" class="x-button x-button-ghost">{{ __('Record Payment') }}</a>
                    <a href="{{ route('accounting.vendor-credits.create') }}?vendor_id={{ $vendor->id }}" class="x-button x-button-ghost">{{ __('Vendor Credit') }}</a>
                    <a href="{{ route('accounting.expenses.create') }}?vendor_id={{ $vendor->id }}" class="x-button x-button-ghost">{{ __('Record Expense') }}</a>
                    @if($featPurchasing)
                    <a href="{{ route('accounting.purchase-orders.create') }}?vendor_id={{ $vendor->id }}" class="x-button x-button-ghost">{{ __('Create PO') }}</a>
                    @endif
                    <a href="{{ route('accounting.aging.ap-detail') }}?vendor_id={{ $vendor->id }}" class="x-button x-button-ghost">{{ __('A/P Aging Detail') }}</a>
                </div>
            </div>

            {{-- Transaction Timeline --}}
            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Transaction Timeline') }}</p>
                @if($timeline->isEmpty())
                    <p class="text-sm text-ink-soft">{{ __('No transactions found for this vendor.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeline as $item)
                                    <tr>
                                        <td>
                                            @switch($item['type'])
                                                @case('bill')
                                                    <span class="status-pill neutral">{{ __('Bill') }}</span>
                                                    @break
                                                @case('payment')
                                                    <span class="status-pill positive">{{ __('Payment') }}</span>
                                                    @break
                                                @case('credit')
                                                    <span class="status-pill neutral">{{ __('Credit') }}</span>
                                                    @break
                                                @case('po')
                                                    <span class="status-pill neutral">{{ __('PO') }}</span>
                                                    @break
                                                @case('expense')
                                                    <span class="status-pill neutral">{{ __('Expense') }}</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>{{ $item['reference'] }}</td>
                                        <td class="text-ink-soft">{{ $item['date'] instanceof \Carbon\Carbon ? $item['date']->format('M d, Y') : $item['date'] }}</td>
                                        <td class="numeric">{{ format_number($item['amount']) }}</td>
                                        <td class="text-center"><span class="text-xs text-ink-soft">{{ ucfirst(str_replace('_', ' ', $item['status'])) }}</span></td>
                                        <td class="text-right"><a href="{{ $item['url'] }}" class="text-ink hover:text-gold">{{ __('View') }}</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
