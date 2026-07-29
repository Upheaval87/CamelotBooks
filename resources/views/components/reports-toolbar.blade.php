@props([
    'showCompare' => false,
    'action' => '',
    'dateFrom' => null,
    'dateTo' => null,
    'dateFromName' => 'date_from',
    'dateToName' => 'date_to',
])

<form method="GET" action="{{ $action }}">
    <x-toolbar class="mb-6">
        <div class="flex items-center gap-2">
            <input type="date" name="{{ $dateFromName }}" value="{{ $dateFrom ?? request($dateFromName, now()->startOfYear()->format('Y-m-d')) }}" class="border border-gray-200 rounded-md px-2 py-1.5 text-sm text-atlas-navy focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:border-transparent w-40" />
            <span class="text-atlas-navy/40 text-sm">to</span>
            <input type="date" name="{{ $dateToName }}" value="{{ $dateTo ?? request($dateToName, now()->format('Y-m-d')) }}" class="border border-gray-200 rounded-md px-2 py-1.5 text-sm text-atlas-navy focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:border-transparent w-40" />
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-atlas-amber text-atlas-navy text-sm font-medium rounded-md hover:brightness-110 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>

        @if($showCompare)
            <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>
            <select name="compare_mode" class="border border-gray-200 rounded-md px-2 py-1.5 text-sm text-atlas-navy focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:border-transparent">
                <option value="">No Comparison</option>
                <option value="prior_period" {{ request('compare_mode') === 'prior_period' ? 'selected' : '' }}>Prior Period</option>
                <option value="year_ago" {{ request('compare_mode') === 'year_ago' ? 'selected' : '' }}>Year Ago</option>
            </select>
        @endif

        <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Export to PDF
        </button>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export to CSV
        </button>

        <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

        <x-dropdown align="left" width="56">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center justify-center w-7 h-7 bg-transparent text-atlas-navy/50 rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="py-1">
                    <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save Report Settings
                    </button>
                    <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Schedule
                    </button>
                </div>
            </x-slot>
        </x-dropdown>
    </x-toolbar>
</form>
