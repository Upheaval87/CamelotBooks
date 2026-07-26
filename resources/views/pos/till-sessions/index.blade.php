<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Till Sessions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Open Till Form --}}
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Open New Till') }}</h3>
                <form method="POST" action="{{ route('pos.till-sessions.open') }}" class="flex items-end gap-4 flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="terminal_id" value="{{ __('Terminal') }}" />
                        <select id="terminal_id" name="terminal_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">-- Select Terminal --</option>
                            @foreach($terminals as $terminal)
                                <option value="{{ $terminal->id }}" {{ old('terminal_id') == $terminal->id ? 'selected' : '' }}>
                                    {{ $terminal->identifier }} – {{ $terminal->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('terminal_id')" class="mt-2" />
                    </div>
                    <div class="w-[180px]">
                        <x-input-label for="opening_float" value="{{ __('Opening Float') }}" />
                        <x-text-input id="opening_float" name="opening_float" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('opening_float', '0.00')" required />
                        <x-input-error :messages="$errors->get('opening_float')" class="mt-2" />
                    </div>
                    <x-primary-button type="submit">{{ __('Open Till') }}</x-primary-button>
                </form>
            </div>

            {{-- Sessions Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Float</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Expected</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opened</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closed</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $session->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $session->terminal?->identifier ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $session->user?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">@money($session->opening_float)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                        {{ $session->expected_cash !== null ? format_money($session->expected_cash) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                        {{ $session->actual_cash_count !== null ? format_money($session->actual_cash_count) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold
                                        {{ $session->variance > 0 ? 'text-green-600' : ($session->variance < 0 ? 'text-red-600' : 'text-gray-900') }}">
                                        {{ $session->variance !== null ? ($session->variance >= 0 ? '+' : '') . format_money($session->variance) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($session->isOpen())
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Open</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Closed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $session->opened_at?->format('M d, H:i') ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $session->closed_at?->format('M d, H:i') ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        @if($session->isOpen())
                                            <button type="button" onclick="document.getElementById('close-modal-{{ $session->id }}').classList.remove('hidden')"
                                                class="text-orange-600 hover:text-orange-900">Close Till</button>
                                        @endif
                                        @if($session->isClosed())
                                            <a href="{{ route('pos.till-sessions.show', $session) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Close Till Modal --}}
                                @if($session->isOpen())
                                    <div id="close-modal-{{ $session->id }}" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
                                        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Close Till – Session #{{ $session->id }}</h3>
                                            <p class="text-sm text-gray-600 mb-4">Terminal: {{ $session->terminal?->identifier }} | Float: @money($session->opening_float)</p>
                                            <form method="POST" action="{{ route('pos.till-sessions.close', $session) }}">
                                                @csrf
                                                <div class="mb-4">
                                                    <x-input-label for="actual_cash_count_{{ $session->id }}" value="{{ __('Actual Cash Count') }}" />
                                                    <x-text-input id="actual_cash_count_{{ $session->id }}" name="actual_cash_count" type="number" step="0.01" min="0"
                                                        class="mt-1 block w-full" :value="old('actual_cash_count', '0.00')" required placeholder="Count the cash in the drawer" />
                                                    <x-input-error :messages="$errors->get('actual_cash_count')" class="mt-2" />
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" onclick="document.getElementById('close-modal-{{ $session->id }}').classList.add('hidden')"
                                                        class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                                                    <x-primary-button type="submit">{{ __('Close Till') }}</x-primary-button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">No till sessions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3">
                    {{ $sessions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
