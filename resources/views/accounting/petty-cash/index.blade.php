<x-app-layout>
    <x-slot name="header">{{ __('Create Fund') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.petty-cash.create-fund') }}">
                    {{ __('Create Fund') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Fund Name</th>
                                <th>Code</th>
                                <th class="text-right">Float</th>
                                <th class="text-right">Current Balance</th>
                                <th class="text-right">Spent</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $fund)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.petty-cash.show', $fund['id']) }}" class="text-ink hover:text-gold">
                                            {{ $fund['name'] }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $fund['code'] }}</td>
                                    <td class="numeric">{{ format_money($fund['float']) }}</td>
                                    <td class="numeric">{{ format_money($fund['current_balance']) }}</td>
                                    <td class="numeric">{{ format_money($fund['spent']) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('accounting.petty-cash.show', $fund['id']) }}" class="text-ink hover:text-gold">Manage</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No petty cash funds found. Create one to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
