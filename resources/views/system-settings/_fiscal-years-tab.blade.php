<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Fiscal Years</h3>
            <p class="mt-1 text-sm text-gray-600">Overview of your company's fiscal years and accounting periods. Create, close, and manage fiscal years from the full manager.</p>
        </div>
        <a href="{{ route('accounting.fiscal-years.index') }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
            Fiscal Year Manager
        </a>
    </div>
    <div class="p-6">
        @if($fiscalYears->isEmpty())
            <div class="text-center py-8">
                <p class="text-sm text-gray-500">No fiscal years configured yet.</p>
                <a href="{{ route('accounting.fiscal-years.index') }}" class="mt-2 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                    Create First Fiscal Year
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($fiscalYears as $fy)
                    @php
                        $openPeriods = $fy->periods->where('status', 'open')->count();
                        $closedPeriods = $fy->periods->where('status', 'closed')->count();
                        $lockedPeriods = $fy->periods->where('status', 'locked')->count();
                        $totalPeriods = $fy->periods->count();
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4 {{ $fy->status === 'open' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-gray-400' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <h4 class="text-sm font-semibold text-gray-900">{{ $fy->label }}</h4>
                                @if($fy->status === 'open')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Open</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Closed</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $fy->start_date->format('M d, Y') }} — {{ $fy->end_date->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                            <span>{{ $totalPeriods }} periods</span>
                            @if($openPeriods > 0)
                                <span class="text-green-600">{{ $openPeriods }} open</span>
                            @endif
                            @if($closedPeriods > 0)
                                <span>{{ $closedPeriods }} closed</span>
                            @endif
                            @if($lockedPeriods > 0)
                                <span>{{ $lockedPeriods }} locked</span>
                            @endif
                            @if($fy->closed_by)
                                <span>Closed by {{ $fy->closedByUser?->name ?? 'Unknown' }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
