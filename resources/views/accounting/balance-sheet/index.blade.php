<x-app-layout>
    <x-list-header title="{{ __('Balance Sheet') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.balance-sheet.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$asOfDate" />
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
                        <a href="{{ route('accounting.balance-sheet.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Balance Sheet"
                    :range="'As of ' . \Carbon\Carbon::parse($asOfDate)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.balance-sheet.export-csv', request()->query())"
                    :pdfRoute="route('accounting.balance-sheet.export-pdf', request()->query())"
                />

                <x-report.col-bar left="Description" :right="'Amount ('.$cs.')'" />

                <x-report.section-bar>Assets</x-report.section-bar>
                @foreach($groups['asset'] as $subType => $items)
                    @if(!empty($items))
                        @foreach($items as $item)
                            <x-report.line
                                :code="$item['account']->code"
                                :desc="$item['account']->name"
                                :amount="format_number($item['balance'])"
                                :href="route('accounting.general-ledger.account', $item['account']->id).'?date_to='.$asOfDate.($branchId ? '&branch_id='.$branchId : '')"
                                :zero="abs($item['balance']) <= 0"
                            />
                        @endforeach
                    @endif
                @endforeach
                <x-report.subtotal desc="Total Assets" :amount="format_number($total_assets)" />

                <x-report.section-bar>Liabilities</x-report.section-bar>
                @foreach($groups['liability'] as $subType => $items)
                    @if(!empty($items))
                        @foreach($items as $item)
                            <x-report.line
                                :code="$item['account']->code"
                                :desc="$item['account']->name"
                                :amount="format_number($item['balance'])"
                                :href="route('accounting.general-ledger.account', $item['account']->id).'?date_to='.$asOfDate.($branchId ? '&branch_id='.$branchId : '')"
                                :zero="abs($item['balance']) <= 0"
                            />
                        @endforeach
                    @endif
                @endforeach
                <x-report.subtotal desc="Total Liabilities" :amount="format_number($total_liabilities)" />

                <x-report.section-bar>Equity</x-report.section-bar>
                @foreach($groups['equity'] as $subType => $items)
                    @foreach($items as $item)
                        <x-report.line
                            :code="$item['account']->code"
                            :desc="$item['account']->name"
                            :amount="format_number($item['balance'])"
                            :href="route('accounting.general-ledger.account', $item['account']->id).'?date_to='.$asOfDate.($branchId ? '&branch_id='.$branchId : '')"
                            :zero="abs($item['balance']) <= 0"
                        />
                    @endforeach
                @endforeach
                <x-report.line desc="Current Year Earnings" :amount="format_number($current_year_earnings)" />
                <x-report.subtotal desc="Total Equity" :amount="format_number($total_equity)" />

                <x-report.total desc="Total Liabilities &amp; Equity" :amount="format_number($total_liabilities + $total_equity)" />
            </x-report.card>

            @if(!$balanced)
                <x-feedback.alert variant="error" class="mt-4">Warning: Balance sheet is out of balance by {{ format_number(abs($total_assets - ($total_liabilities + $total_equity))) }}</x-feedback.alert>
            @endif
        </div>
    </div>
</x-app-layout>
