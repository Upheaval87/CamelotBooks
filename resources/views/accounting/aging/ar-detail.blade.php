<x-app-layout>
    <x-slot name="header">{{ __('A/R Aging Detail') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.aging.ar-detail') }}" class="flex items-end gap-4">
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
                        <a href="{{ route('accounting.aging.ar-detail') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-4 flex gap-2">
                <a href="{{ route('accounting.aging.ar-summary', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Summary</a>
                <a href="{{ route('accounting.aging.ar-detail', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm">Detail</a>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="A/R Aging Detail"
                    :range="'As of ' . \Carbon\Carbon::parse($as_of_date)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ar']))"
                />

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Invoice #</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th class="report-col-amt">Amount Due ({{ $cs }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $row)
                            @php $zero = $row['total'] <= 0; @endphp
                            <tr class="@if($zero) zero @endif">
                                <td>{{ $row['customer_name'] }}</td>
                                <td>{{ $row['invoice_number'] ?? '-' }}</td>
                                <td>{{ $row['due_date'] ?? '-' }}</td>
                                <td>{{ $row['days_overdue'] ?? 0 }}</td>
                                <td class="report-cell-amt">{{ format_number($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-ink-soft" style="padding:20px 14px">No outstanding invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="report-total-row">
                            <td colspan="4" style="text-align:right">Total</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($totals['total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </x-report.card>
        </div>
    </div>
</x-app-layout>
