<x-app-layout>
    <x-slot name="header">{{ $table->version_name }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.paye-tables.edit', $table) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                @if(!$table->is_current)
                    <form method="POST" action="{{ route('accounting.paye-tables.activate', $table) }}" class="inline" onsubmit="return confirm('Are you sure you want to activate this PAYE tax table?');">
                        @csrf
                        <button type="submit" class="tr-save">{{ __('Activate') }}</button>
                    </form>
                    <form method="POST" action="{{ route('accounting.paye-tables.destroy', $table) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this PAYE tax table?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tr-archive">{{ __('Delete') }}</button>
                    </form>
                @endif
                <a href="{{ route('accounting.paye-tables.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Version') }}" strong>{{ $table->version_name }}</x-detail-field>
                    <x-detail-field label="{{ __('Effective From') }}">{{ $table->effective_from->format('d M Y') }}</x-detail-field>
                    <x-detail-field label="{{ __('Effective To') }}">{{ $table->effective_to ? $table->effective_to->format('d M Y') : '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Status') }}">
                        @if($table->is_current)
                            <span class="status-pill positive">{{ __('Active') }}</span>
                        @else
                            <span class="status-pill neutral">{{ __('Inactive') }}</span>
                        @endif
                    </x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Tax Bands') }}</p>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Threshold') }}</th>
                                <th>{{ __('Upper Limit') }}</th>
                                <th>{{ __('Rate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($table->bands->sortBy('sort_order') as $band)
                                <tr>
                                    <td class="text-ink-soft">{{ $band->sort_order + 1 }}</td>
                                    <td>{{ format_money((float) $band->threshold) }}</td>
                                    <td>{{ $band->upper_limit ? format_money((float) $band->upper_limit) : __('No limit') }}</td>
                                    <td>{{ format_money((float) $band->rate) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">{{ __('No bands defined.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
