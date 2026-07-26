@props(['lastRefresh' => null, 'lastRefreshAge' => null])

@if($lastRefresh)
    <div class="mb-4 flex items-center text-sm {{ $lastRefreshAge && \Carbon\Carbon::parse($lastRefresh->completed_at)->diffInMinutes(now()) > 1440 ? 'text-amber-600' : 'text-gray-500' }}">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Data refreshed {{ $lastRefreshAge }} &middot;
        {{ number_format($lastRefresh->rows_refreshed->fact_general_ledger ?? 0) }} GL rows,
        {{ number_format($lastRefresh->rows_refreshed->fact_sales ?? 0) }} sales,
        {{ number_format($lastRefresh->rows_refreshed->fact_purchases ?? 0) }} purchases,
        {{ number_format($lastRefresh->rows_refreshed->fact_payroll ?? 0) }} payroll
        @if(\Carbon\Carbon::parse($lastRefresh->completed_at)->diffInMinutes(now()) > 1440)
            <span class="ml-2 text-amber-600 font-medium">(stale — run php artisan bi:refresh-data-mart)</span>
        @endif
    </div>
@else
    <div class="mb-4 text-sm text-red-600">
        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        No data mart refresh found. Run <code class="bg-gray-100 px-1 rounded">php artisan bi:refresh-data-mart</code> to populate BI data.
    </div>
@endif
