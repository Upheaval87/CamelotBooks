<x-app-layout>
    <x-slot name="header">{{ __('Cash Flow Statement') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.cash-flow.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="date_from" value="{{ __('Date From') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$dateFrom" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="{{ __('Date To') }}" />
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
                        <a href="{{ route('accounting.cash-flow.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Cash Flow Statement"
                    :range="\Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' — ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.cash-flow.export-csv', request()->query())"
                    :pdfRoute="route('accounting.cash-flow.export-pdf', request()->query())"
                />

                <x-report.col-bar left="Description" :right="'Amount ('.$cs.')'" />

                <x-report.section-bar>Operating Activities</x-report.section-bar>
                <x-report.line desc="Net Income" :amount="format_number($net_income)" />
                @foreach($non_cash_expenses['items'] as $item)
                    <x-report.line :desc="'Add: '.$item['account']->name" :amount="format_number($item['amount'])" :zero="abs($item['amount']) <= 0" />
                @endforeach
                @foreach($sections['operating'] as $item)
                    <x-report.line :desc="($item['change'] > 0 ? 'Increase in' : 'Decrease in').' '.$item['account']->name" :amount="format_number($item['cash_effect'])" :zero="abs($item['cash_effect']) <= 0" />
                @endforeach
                <x-report.subtotal desc="Net Cash from Operating" :amount="format_number($operating_total)" />

                <x-report.section-bar>Investing Activities</x-report.section-bar>
                @forelse($sections['investing'] as $item)
                    <x-report.line :desc="($item['change'] > 0 ? 'Increase in' : 'Decrease in').' '.$item['account']->name" :amount="format_number($item['cash_effect'])" :zero="abs($item['cash_effect']) <= 0" />
                @empty
                    <x-report.line desc="No investing activities" :amount="format_number(0)" :zero="true" />
                @endforelse
                <x-report.subtotal desc="Net Cash from Investing" :amount="format_number($investing_total)" />

                <x-report.section-bar>Financing Activities</x-report.section-bar>
                @forelse($sections['financing'] as $item)
                    <x-report.line :desc="($item['change'] > 0 ? 'Increase in' : 'Decrease in').' '.$item['account']->name" :amount="format_number($item['cash_effect'])" :zero="abs($item['cash_effect']) <= 0" />
                @empty
                    <x-report.line desc="No financing activities" :amount="format_number(0)" :zero="true" />
                @endforelse
                <x-report.subtotal desc="Net Cash from Financing" :amount="format_number($financing_total)" />

                <x-report.total desc="Net Change in Cash" :amount="format_number($net_change)" />
                <x-report.line desc="Beginning Cash Balance" :amount="format_number($beginning_cash)" />
                <x-report.subtotal desc="Ending Cash Balance" :amount="format_number($ending_cash)" />
            </x-report.card>

            @if($mismatch)
                <div class="mt-4 px-6 py-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm font-semibold text-red-600">Warning: Ending cash does not match actual bank balances. Difference: {{ format_number($mismatch) }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
