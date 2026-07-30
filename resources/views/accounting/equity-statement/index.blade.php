<x-app-layout>
    <x-slot name="header">{{ __('Statement of Changes in Equity') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.equity-statement.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="date_from" value="{{ __('From Date') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$dateFrom" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="{{ __('To Date') }}" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$dateTo" />
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
                        <a href="{{ route('accounting.equity-statement.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Statement of Changes in Equity"
                    :range="\Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' — ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.equity-statement.export-csv', request()->query())"
                    :pdfRoute="route('accounting.equity-statement.export-pdf', request()->query())"
                />

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="report-col-amt">Opening ({{ $cs }})</th>
                            <th class="report-col-amt">Movement ({{ $cs }})</th>
                            <th class="report-col-amt">Closing ({{ $cs }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $item)
                            @php $zero = abs($item['movement']) <= 0 && abs($item['opening']) <= 0 && abs($item['closing']) <= 0; @endphp
                            <tr class="@if($zero) zero @endif">
                                <td><span class="report-cell-code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</td>
                                <td class="report-cell-amt">{{ format_number($item['opening']) }}</td>
                                <td class="report-cell-amt">{{ $item['movement'] >= 0 ? '+' : '' }}{{ format_number($item['movement']) }}</td>
                                <td class="report-cell-amt">{{ format_number($item['closing']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-ink-soft" style="padding:20px 14px">No equity accounts found.</td>
                            </tr>
                        @endforelse
                        <tr class="report-subtotal-row">
                            <td>Net Income for Period</td>
                            <td class="report-cell-amt"></td>
                            <td class="report-cell-amt">{{ $net_income >= 0 ? '+' : '' }}{{ format_number($net_income) }}</td>
                            <td class="report-cell-amt"></td>
                        </tr>
                        <tr class="report-total-row">
                            <td>Total Equity</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($total_opening) }}</td>
                            <td class="report-cell-amt report-total-val">{{ ($total_closing - $total_opening) >= 0 ? '+' : '' }}{{ format_number($total_closing - $total_opening) }}</td>
                            <td class="report-cell-amt report-total-val">{{ format_number($total_closing) }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-report.card>
        </div>
    </div>
</x-app-layout>
