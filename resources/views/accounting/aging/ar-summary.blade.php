<x-app-layout>
    <x-list-header title="{{ __('A/R Aging Summary') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.aging.ar-summary') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$as_of_date" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="request('branch_id')"
                            :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                            placeholder="{{ __('All Branches') }}"
                        />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.aging.ar-summary') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-4 flex gap-2">
                <a href="{{ route('accounting.aging.ar-summary', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-gold-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm">Summary</a>
                <a href="{{ route('accounting.aging.ar-detail', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Detail</a>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="A/R Aging Summary"
                    :range="'As of ' . \Carbon\Carbon::parse($as_of_date)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ar']))"
                />

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th class="report-col-amt">Current ({{ $cs }})</th>
                            <th class="report-col-amt">1-30 Days ({{ $cs }})</th>
                            <th class="report-col-amt">31-60 Days ({{ $cs }})</th>
                            <th class="report-col-amt">61-90 Days ({{ $cs }})</th>
                            <th class="report-col-amt">90+ Days ({{ $cs }})</th>
                            <th class="report-col-amt">Total ({{ $cs }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $row)
                            @php $zero = $row['total'] <= 0; @endphp
                            <tr class="@if($zero) zero @endif">
                                <td>{{ $row['customer_name'] }}</td>
                                <td class="report-cell-amt">{{ format_number($row['current']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_1_30']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_31_60']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_61_90']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_90_plus']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-ink-soft" style="padding:20px 14px">No outstanding invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="report-total-row">
                            <td>Total</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['current']) }}</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['days_1_30']) }}</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['days_31_60']) }}</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['days_61_90']) }}</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['days_90_plus']) }}</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </x-report.card>
        </div>
    </div>
</x-app-layout>
