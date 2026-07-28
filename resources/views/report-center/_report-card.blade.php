@props(['report', 'isFavorite' => false])

<div class="bg-white shadow-sm sm:rounded-lg p-4 hover:shadow-md transition-shadow duration-200 relative"
     x-data="reportFavorite" data-key="{{ $report['key'] }}" data-favorited="{{ $isFavorite ? 'true' : 'false' }}">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <a href="{{ Route::has($report['route']) ? route($report['route']) : '#' }}"
               class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 {{ Route::has($report['route']) ? '' : 'opacity-50 pointer-events-none' }}">
                {{ $report['name'] }}
            </a>
            <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ $report['description'] }}</p>
        </div>
        <button @click="toggle()" class="ml-2 shrink-0 p-1 rounded-full hover:bg-gray-100 transition-colors"
                :class="favorited ? 'text-yellow-500' : 'text-gray-300 hover:text-gray-400'">
            <svg class="w-4 h-4" :fill="favorited ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
        </button>
    </div>
    @if(!empty($report['feature_flag']))
        <div class="mt-2">
            @foreach((array)$report['feature_flag'] as $flag)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                    {{ $flag }}
                </span>
            @endforeach
        </div>
    @endif
</div>
