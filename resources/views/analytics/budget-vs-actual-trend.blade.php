<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Budget vs Actual Trend</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('analytics.budget-vs-actual-trend') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="fiscal_year_id" value="Fiscal Year" />
                        <select id="fiscal_year_id" name="fiscal_year_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach($fiscalYears as $fy)
                                <option value="{{ $fy->id }}" {{ $fy->id == $fiscalYearId ? 'selected' : '' }}>{{ $fy->name ?? $fy->start_date . ' - ' . $fy->end_date }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="branch_id" value="Branch" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Branches</option>
                            @if(isset($currentBranches))
                                @foreach($currentBranches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            @endif
                        </select>
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
                        <div class="text-2xl font-bold text-indigo-600">${{ number_format($data['total_budget'], 2) }}</div>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-xs text-gray-500 uppercase">Total Actual</div>
                        <div class="text-2xl font-bold text-gray-800">${{ number_format($data['total_actual'], 2) }}</div>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-xs text-gray-500 uppercase">Total Variance</div>
                        <div class="text-2xl font-bold {{ ($data['total_budget'] - $data['total_actual']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format(abs($data['total_budget'] - $data['total_actual']), 2) }}
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
