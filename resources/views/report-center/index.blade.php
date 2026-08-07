<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Report Center" description="Browse and access all available reports.">
        <form method="GET" action="{{ route('accounting.report-center.index') }}" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search reports..."
                   class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm w-64" />
            <x-primary-button>Search</x-primary-button>
            @if($search)
                <a href="{{ route('accounting.report-center.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </x-list-header>

    @if(!empty($favoriteReports))
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                Favorites
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($favoriteReports as $report)
                    @include('report-center._report-card', ['report' => $report, 'isFavorite' => true])
                @endforeach
            </div>
        </div>
    @endif

    @forelse($grouped as $catKey => $catData)
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ $catData['label'] }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($catData['reports'] as $report)
                    @include('report-center._report-card', ['report' => $report, 'isFavorite' => in_array($report['key'], session('report_favorites', []))])
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-2 text-sm text-gray-500">No reports match your search.</p>
        </div>
    @endforelse
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportFavorite', () => ({
        favorited: false,
        init() {
            this.favorited = this.$el.dataset.favorited === 'true';
        },
        toggle() {
            fetch(`/accounting/report-center/favorite/${this.$el.dataset.key}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                this.favorited = data.favorited;
            });
        },
    }));
});
</script>
</x-app-layout>