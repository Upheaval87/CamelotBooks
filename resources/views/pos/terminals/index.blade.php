<x-app-layout>
    <x-list-header title="{{ __('POS Terminals') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            <div class="mb-6 card p-6">
                <div class="form-section-label">1 · Add Terminal</div>
                <form method="POST" action="{{ route('pos.terminals.store') }}" class="flex items-end gap-4 flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-[150px]">
                        <x-input-label for="identifier" value="{{ __('Identifier') }}" />
                        <x-text-input id="identifier" name="identifier" type="text" class="mt-1 block w-full" :value="old('identifier')" required placeholder="e.g. T1, REGISTER-01" />
                        <x-input-error :messages="$errors->get('identifier')" class="mt-2" />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="name" value="{{ __('Name') }}" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="e.g. Front Counter" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="old('branch_id')"
                            :label="old('branch_id') ? (($branches->firstWhere('id', (int) old('branch_id'))?->code ?? '') . ' - ' . ($branches->firstWhere('id', (int) old('branch_id'))?->name ?? '')) : ''"
                            placeholder="{{ __('No branch') }}"
                        />
                        <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                    </div>
                    <div class="w-[200px]">
                        <x-input-label for="cashier_pin_timeout_minutes" value="{{ __('PIN Timeout (min)') }}" />
                        <x-text-input id="cashier_pin_timeout_minutes" name="cashier_pin_timeout_minutes" type="number" class="mt-1 block w-full" :value="old('cashier_pin_timeout_minutes', 0)" min="0" max="480" />
                        <x-input-error :messages="$errors->get('cashier_pin_timeout_minutes')" class="mt-2" />
                    </div>
                    <x-primary-button type="submit">{{ __('Add') }}</x-primary-button>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Identifier</th>
                                <th>Name</th>
                                <th>Branch</th>
                                <th class="text-center">PIN Timeout</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($terminals as $terminal)
                                <tr class="{{ $terminal->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                    <td>{{ $terminal->identifier }}</td>
                                    <td>{{ $terminal->name }}</td>
                                    <td class="text-ink-soft">{{ $terminal->branch?->name ?? '—' }}</td>
                                    <td class="text-center text-ink-soft">
                                        {{ $terminal->cashier_pin_timeout_minutes > 0 ? $terminal->cashier_pin_timeout_minutes . ' min' : 'Disabled' }}
                                    </td>
                                    <td class="text-center">
                                        @if($terminal->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill negative">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('pos.terminals.toggle', $terminal) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $terminal->is_active ? 'red' : 'green' }}-600 hover:text-{{ $terminal->is_active ? 'red' : 'green' }}-900">
                                                {{ $terminal->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-ink-soft text-center">No terminals found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
