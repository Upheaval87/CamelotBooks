<x-app-layout>
    <x-list-header title="{{ __('Income Statement') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.income-statement.index') }}" class="flex items-end gap-4">
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
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="request('branch_id')"
                            :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                            placeholder="{{ __('All Branches') }}"
                        />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="compare_mode" value="{{ __('Comparison') }}" />
                        <select id="compare_mode" name="compare_mode" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">No Comparison</option>
                            <option value="prior_period" {{ ($compareMode ?? '') === 'prior_period' ? 'selected' : '' }}>Prior Period</option>
                            <option value="year_ago" {{ ($compareMode ?? '') === 'year_ago' ? 'selected' : '' }}>Year Ago</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.income-statement.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Income Statement"
                    :range="\Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' — ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.income-statement.export-csv', request()->query())"
                    :pdfRoute="route('accounting.income-statement.export-pdf', request()->query())"
                />

                <x-report.col-bar left="Description" :right="'Amount ('.$cs.')'" />

                <x-report.section-bar>Income</x-report.section-bar>
                @foreach($groups['income'] as $subType => $items)
                    @foreach($items as $item)
                        <x-report.line
                            :code="$item['account']->code"
                            :desc="$item['account']->name"
                            :amount="format_number(max(0, $item['net']))"
                            :href="route('accounting.general-ledger.account', $item['account']->id).'?date_from='.$dateFrom.'&date_to='.$dateTo.($branchId ? '&branch_id='.$branchId : '')"
                            :zero="max(0, $item['net']) <= 0"
                        />
                    @endforeach
                @endforeach
                <x-report.subtotal desc="Total Income" :amount="format_number($total_income)" />

                <x-report.section-bar>Expenses</x-report.section-bar>
                @foreach($groups['expense'] as $subType => $items)
                    @foreach($items as $item)
                        <x-report.line
                            :code="$item['account']->code"
                            :desc="$item['account']->name"
                            :amount="format_number(max(0, $item['net']))"
                            :href="route('accounting.general-ledger.account', $item['account']->id).'?date_from='.$dateFrom.'&date_to='.$dateTo.($branchId ? '&branch_id='.$branchId : '')"
                            :zero="max(0, $item['net']) <= 0"
                        />
                    @endforeach
                @endforeach
                <x-report.subtotal desc="Total Expenses" :amount="format_number($total_expenses)" />

                <x-report.total
                    :desc="$net_income >= 0 ? 'Net Income' : 'Net Loss'"
                    :amount="format_number(abs($net_income))"
                />

                @if(!empty($comparison))
                    <div class="mt-8 pt-4 border-t border-ink/10 text-xs text-ink-muted">
                        <p>Comparison ({{ $compareMode === 'prior_period' ? 'Prior Period' : 'Year Ago' }}):
                            Income: {{ format_number($comparison['total_income'] ?? 0) }},
                            Expenses: {{ format_number($comparison['total_expenses'] ?? 0) }},
                            Net: {{ format_number(abs($comparison['net_income'] ?? 0)) }}
                        </p>
                    </div>
                @endif
            </x-report.card>
        </div>
    </div>
</x-app-layout>
