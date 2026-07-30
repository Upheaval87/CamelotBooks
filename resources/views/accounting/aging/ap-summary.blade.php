<x-app-layout>
    <x-slot name="header">{{ __('A/P Aging Summary') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.aging.ap-summary') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$as_of_date" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.aging.ap-summary') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-4 flex gap-2">
                <a href="{{ route('accounting.aging.ap-summary', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm">Summary</a>
                <a href="{{ route('accounting.aging.ap-detail', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Detail</a>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="A/P Aging Summary"
                    :range="'As of ' . \Carbon\Carbon::parse($as_of_date)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ap']))"
                />

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th class="report-col-amt">Current ({{ $cs }})</th>
                            <th class="report-col-amt">1-30 Days ({{ $cs }})</th>
                            <th class="report-col-amt">31-60 Days ({{ $cs }})</th>
                            <th class="report-col-amt">61-90 Days ({{ $cs }})</th>
                            <th class="report-col-amt">90+ Days ({{ $cs }})</th>
                            <th class="report-col-amt">Total ({{ $cs }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $row)
                            @php $zero = $row['total'] <= 0; @endphp
                            <tr class="@if($zero) zero @endif">
                                <td>{{ $row['vendor_name'] }}</td>
                                <td class="report-cell-amt">{{ format_number($row['current']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_1_30']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_31_60']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_61_90']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['days_90_plus']) }}</td>
                                <td class="report-cell-amt">{{ format_number($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-ink-soft" style="padding:20px 14px">No outstanding bills found.</td>
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
