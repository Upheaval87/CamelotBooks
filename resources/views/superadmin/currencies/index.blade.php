<x-app-layout>

    <div class="sa-page py-6" style="background: #F8F9FC;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Currencies') }}</h1>
                    <p class="sa-page-subtitle">{{ __('Base currency options shown in company setup and tenant Settings.') }}</p>
                </div>
                <a href="{{ route('superadmin.currencies.create') }}" class="sa-btn sa-btn--primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New Currency') }}
                </a>
            </div>

            <x-elevated-card :flush="true">
                <div class="sa-table-wrap">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Symbol') }}</th>
                                <th>{{ __('Position') }}</th>
                                <th>{{ __('Sort') }}</th>
                                <th class="sa-table-center">{{ __('Status') }}</th>
                                <th class="sa-table-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($currencies as $currency)
                                <tr>
                                    <td><span class="sa-table-primary">{{ $currency->code }}</span></td>
                                    <td><span style="color: var(--sa-muted);">{{ $currency->name }}</span></td>
                                    <td><span class="sa-table-mono">{{ $currency->symbol ?: '—' }}</span></td>
                                    <td><span style="color: var(--sa-muted);">{{ $currency->symbol_position }}</span></td>
                                    <td><span style="color: var(--sa-muted);">{{ $currency->sort_order }}</span></td>
                                    <td class="sa-table-center">
                                        @if($currency->is_active)
                                            <span class="sa-pill sa-pill--accent">{{ __('Active') }}</span>
                                        @else
                                            <span class="sa-pill sa-pill--muted">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="sa-table-center">
                                        <a href="{{ route('superadmin.currencies.edit', $currency) }}" class="sa-btn sa-btn--tint">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('superadmin.currencies.toggle', $currency) }}" class="inline" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="sa-btn sa-btn--ghost sa-btn--sm" style="margin-left: 8px;">
                                                {{ $currency->is_active ? __('Disable') : __('Enable') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="sa-table-empty">{{ __('No currencies yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-elevated-card>
        </div>
    </div>
</x-app-layout>
