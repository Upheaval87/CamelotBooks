@props([
    'mode' => 'period',
    'showBranch' => true,
    'showCostCenter' => true,
    'showCompare' => false,
    'showDimension' => false,
    'dimensions' => [],
    'action' => '',
    'method' => 'GET',
])

<form method="{{ $method }}" action="{{ $action }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @if($mode === 'period')
            <div>
                <x-input-label for="date_from" value="From" />
                <x-text-input id="date_from" name="date_from" type="date" :value="request('date_from', now()->startOfYear()->format('Y-m-d'))" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="date_to" value="To" />
                <x-text-input id="date_to" name="date_to" type="date" :value="request('date_to', now()->format('Y-m-d'))" class="mt-1 block w-full" />
            </div>
        @else
            <div>
                <x-input-label for="as_of_date" value="As of Date" />
                <x-text-input id="as_of_date" name="as_of_date" type="date" :value="request('as_of_date', now()->format('Y-m-d'))" class="mt-1 block w-full" />
            </div>
        @endif

        @if($showBranch && isset($currentBranches) && $currentBranches->count() > 0)
            <div>
                <x-input-label for="branch_id" value="Branch" />
                <x-scoped-search-field
                    name="branch_id"
                    entity="branch"
                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                    :value="request('branch_id')"
                    :label="request('branch_id') ? ($currentBranches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                    placeholder="{{ __('All Branches') }}"
                />
            </div>
        @endif

        @if($showCostCenter)
            <div>
                <x-input-label for="cost_center_id" value="Cost Center" />
                @php
                    $costCenters = \App\Models\CostCenter::where('company_id', session('current_company_id'))->where('is_active', true)->get();
                @endphp
                <x-scoped-search-field
                    name="cost_center_id"
                    entity="cost-center"
                    search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                    :value="request('cost_center_id')"
                    :label="request('cost_center_id') ? ($costCenters->firstWhere('id', (int) request('cost_center_id'))?->name ?? '') : ''"
                    placeholder="{{ __('All Cost Centers') }}"
                />
            </div>
        @endif

        @if($showCompare)
            <div>
                <x-input-label for="compare_mode" value="Compare" />
                <select id="compare_mode" name="compare_mode" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">No Comparison</option>
                    <option value="prior_period" {{ request('compare_mode') === 'prior_period' ? 'selected' : '' }}>Prior Period</option>
                    <option value="year_ago" {{ request('compare_mode') === 'year_ago' ? 'selected' : '' }}>Year Ago</option>
                </select>
            </div>
        @endif

        @if($showDimension)
            <div>
                <x-input-label for="dimension" value="Group By" />
                <select id="dimension" name="dimension" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @foreach($dimensions as $key => $label)
                        <option value="{{ $key }}" {{ request('dimension') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="mt-4 flex justify-end">
        <x-primary-button>Apply Filters</x-primary-button>
    </div>
</form>
