<x-app-layout>
    <x-slot name="header">{{ __('Currencies') }}</x-slot>

    @include('superadmin._nav', ['active' => 'currencies'])

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="card p-6">
                <h3 class="text-sm font-semibold text-ink mb-6">{{ __('Currency Catalog') }}</h3>
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-500">{{ __('Base currency options shown in company setup and tenant Settings.') }}</p>
                    <a href="{{ route('superadmin.currencies.create') }}" class="list-header-create">{{ __('New Currency') }}</a>
                </div>

                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Symbol') }}</th>
                                <th>{{ __('Position') }}</th>
                                <th>{{ __('Sort') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($currencies as $currency)
                                <tr>
                                    <td class="font-medium text-ink">{{ $currency->code }}</td>
                                    <td>{{ $currency->name }}</td>
                                    <td class="font-mono">{{ $currency->symbol ?: '—' }}</td>
                                    <td>{{ $currency->symbol_position }}</td>
                                    <td>{{ $currency->sort_order }}</td>
                                    <td>
                                        @if($currency->is_active)
                                            <x-status-badge variant="success">Active</x-status-badge>
                                        @else
                                            <x-status-badge variant="default">Inactive</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('superadmin.currencies.edit', $currency) }}" class="text-sm text-accent hover:underline mr-3">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('superadmin.currencies.toggle', $currency) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-gray-500 hover:text-brick">
                                                {{ $currency->is_active ? __('Disable') : __('Enable') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-gray-500">{{ __('No currencies yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
