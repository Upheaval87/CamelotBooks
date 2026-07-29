<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cost Centers') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Add Cost Center') }}</h3>
                <form method="POST" action="{{ route('accounting.cost-centers.store') }}" class="flex items-end gap-4">
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
                        <x-input-label for="description" value="{{ __('Description') }}" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" />
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
                                <th>Description</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costCenters as $costCenter)
                                <tr class="{{ $costCenter->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                    <td>{{ $costCenter->code }}</td>
                                    <td>{{ $costCenter->name }}</td>
                                    <td class="text-ink-soft">{{ $costCenter->description ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($costCenter->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill neutral">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('accounting.cost-centers.toggle', $costCenter) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $costCenter->is_active ? 'red' : 'green' }}-600 hover:text-{{ $costCenter->is_active ? 'red' : 'green' }}-900">
                                                {{ $costCenter->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">No cost centers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
