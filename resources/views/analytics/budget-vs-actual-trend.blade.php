<x-app-layout>
    <x-list-header title="Budget vs Actual Trend" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('analytics.budget-vs-actual-trend') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="fiscal_year_id" value="Fiscal Year" />
                        @php $selectedFy = request('fiscal_year_id') ? $fiscalYears->firstWhere('id', (int) request('fiscal_year_id')) : null; @endphp
                        <x-scoped-search-field
                            name="fiscal_year_id"
                            entity="fiscal-year"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'fiscal-year']) }}"
                            :value="request('fiscal_year_id')"
                            :label="$selectedFy ? ($selectedFy->name ?? $selectedFy->start_date . ' - ' . $selectedFy->end_date) : ''"
                            placeholder="{{ __('All Fiscal Years') }}"
                        />
                    </div>
                    <div>
                        <x-input-label for="branch_id" value="Branch" />
                        @if(isset($currentBranches))
                            <x-scoped-search-field
                                name="branch_id"
                                entity="branch"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                :value="request('branch_id')"
                                :label="request('branch_id') ? ($currentBranches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                                placeholder="{{ __('All Branches') }}"
                            />
                        @endif
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Apply</x-primary-button>
                    </div>
                </div>
            </form>

            @if(isset($data['error']))
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-500">{{ $data['error'] }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-xs text-gray-500 uppercase">Total Budget</div>
                        <div class="text-2xl font-bold text-gold-700">@money($data['total_budget'])</div>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-xs text-gray-500 uppercase">Total Actual</div>
                        <div class="text-2xl font-bold text-gray-800">@money($data['total_actual'])</div>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-xs text-gray-500 uppercase">Total Variance</div>
                        <div class="text-2xl font-bold {{ ($data['total_budget'] - $data['total_actual']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @money(abs($data['total_budget'] - $data['total_actual']))
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Cumulative Budget vs Actual</h3>
                    <x-chart type="line" :id="'budget-vs-actual'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                        ['label' => 'Budget (Cumulative)', 'data' => $data['budget_data'], 'borderColor' => '#6366f1', 'backgroundColor' => 'rgba(99,102,241,0.1)', 'fill' => false, 'borderWidth' => 2],
                        ['label' => 'Actual (Cumulative)', 'data' => $data['actual_data'], 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16,185,129,0.1)', 'fill' => false, 'borderWidth' => 2],
                        ['label' => 'Variance', 'data' => $data['variance_data'], 'borderColor' => '#f59e0b', 'backgroundColor' => 'rgba(245,158,11,0.1)', 'fill' => true, 'borderDash' => [5,5]],
                    ])" height="350" />
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
