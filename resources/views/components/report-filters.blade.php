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
                <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Branches</option>
                    @foreach($currentBranches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($showCostCenter)
            <div>
                <x-input-label for="cost_center_id" value="Cost Center" />
                <select id="cost_center_id" name="cost_center_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Cost Centers</option>
                    @php
                        $costCenters = \App\Models\CostCenter::where('company_id', session('current_company_id'))->where('is_active', true)->get();
                    @endphp
                    @foreach($costCenters as $cc)
                        <option value="{{ $cc->id }}" {{ request('cost_center_id') == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                    @endforeach
                </select>
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
