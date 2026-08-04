<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('New Vendor') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.vendors.create') }}">
                    {{ __('New Vendor') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th class="text-right">Total Bills ({{ $cs }})</th>
                                <th class="text-right">Total Paid ({{ $cs }})</th>
                                <th class="text-right">Open Balance ({{ $cs }})</th>
                                <th class="text-right">Open POs</th>
                                <th class="text-right">Credit Balance ({{ $cs }})</th>
                                <th class="text-right">Expenses ({{ $cs }})</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendors as $vendor)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.vendor-centre.show', $vendor) }}" class="text-ink hover:text-gold">
                                            {{ $vendor->name }}
                                        </a>
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($vendor->total_bills) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($vendor->total_paid) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $vendor->open_balance > 0 ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                        {{ format_number($vendor->open_balance) }}
                                    </td>
                                    <td class="numeric">
                                        {{ $vendor->open_pos }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $vendor->credit_balance > 0 ? 'text-green-600 font-semibold' : 'text-gray-900' }}">
                                        {{ format_number($vendor->credit_balance) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($vendor->expense_total) }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.vendor-centre.show', $vendor) }}" class="text-ink hover:text-gold">View Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-ink-soft">
                                        No active vendors found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
