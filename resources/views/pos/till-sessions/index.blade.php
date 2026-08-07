<x-app-layout>
    <x-list-header title="{{ __('Till Sessions') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            {{-- Open Till Form --}}
            <div class="mb-6 card p-6">
                <div class="form-section-label">1 · Open New Till</div>
                <form method="POST" action="{{ route('pos.till-sessions.open') }}" class="flex items-end gap-4 flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="terminal_id" value="{{ __('Terminal') }}" />
                        <select id="terminal_id" name="terminal_id" class="input mt-1" required>
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
            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Terminal</th>
                                <th>Cashier</th>
                                <th class="text-right">Float</th>
                                <th class="text-right">Expected</th>
                                <th class="text-right">Actual</th>
                                <th class="text-right">Variance</th>
                                <th class="text-center">Status</th>
                                <th>Opened</th>
                                <th>Closed</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td>{{ $session->id }}</td>
                                    <td>{{ $session->terminal?->identifier ?? '—' }}</td>
                                    <td>{{ $session->user?->name ?? '—' }}</td>
                                    <td class="numeric">@money($session->opening_float)</td>
                                    <td class="numeric">
                                        {{ $session->expected_cash !== null ? format_money($session->expected_cash) : '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ $session->actual_cash_count !== null ? format_money($session->actual_cash_count) : '—' }}
                                    </td>
                                    <td class="numeric font-semibold
                                        {{ $session->variance > 0 ? 'text-green-600' : ($session->variance < 0 ? 'text-red-600' : '') }}">
                                        {{ $session->variance !== null ? ($session->variance >= 0 ? '+' : '') . format_money($session->variance) : '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($session->isOpen())
                                            <span class="status-pill positive">Open</span>
                                        @else
                                            <span class="status-pill negative">Closed</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">{{ $session->opened_at?->format('M d, H:i') ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $session->closed_at?->format('M d, H:i') ?? '—' }}</td>
                                    <td class="text-right">
                                        @if($session->isOpen())
                                            <button type="button" onclick="document.getElementById('close-modal-{{ $session->id }}').classList.remove('hidden')"
                                                class="text-orange-600 hover:text-orange-900">Close Till</button>
                                        @endif
                                        @if($session->isClosed())
                                            <a href="{{ route('pos.till-sessions.show', $session) }}" class="text-gold-700 hover:text-gold-800">View</a>
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
                                    <td colspan="11" class="text-ink-soft text-center">No till sessions found.</td>
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
