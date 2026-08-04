<x-app-layout>
    <x-list-header title="{{ __('Branches') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 card p-6">
                <div class="form-section-label">1 · Add Branch</div>
                <form method="POST" action="{{ route('branches.store') }}" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="code" value="{{ __('Code') }}" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="name" value="{{ __('Name') }}" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="address" value="{{ __('Address') }}" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address')" />
                    </div>
                    <x-primary-button type="submit">{{ __('Add') }}</x-primary-button>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $branch)
                                <tr class="{{ $branch->is_active ? '' : 'text-ink-soft' }}">
                                    <td>{{ $branch->code }}</td>
                                    <td>{{ $branch->name }}</td>
                                    <td class="text-ink-soft">{{ $branch->address ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($branch->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill negative">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('branches.toggle', $branch) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $branch->is_active ? 'red' : 'green' }}-600 hover:text-{{ $branch->is_active ? 'red' : 'green' }}-900">
                                                {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-ink-soft text-center">No branches found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
