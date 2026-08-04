<x-app-layout>
    <x-list-header title="{{ __('Add Tax Table') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.paye-tables.create') }}">
                    {{ __('Add Tax Table') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Effective From</th>
                                <th>Effective To</th>
                                <th class="text-center">Bands</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tables as $table)
                                <tr class="{{ $table->is_current ? '' : 'bg-gray-50' }}">
                                    <td>
                                        <a href="{{ route('accounting.paye-tables.show', $table) }}" class="text-ink hover:text-gold">{{ $table->version_name }}</a>
                                    </td>
                                    <td>{{ $table->effective_from->format('d M Y') }}</td>
                                    <td class="text-ink-soft">{{ $table->effective_to ? $table->effective_to->format('d M Y') : '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">{{ $table->bands->count() }}</td>
                                    <td class="text-center">
                                        @if($table->is_current)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill neutral">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if(!$table->is_current)
                                            <form method="POST" action="{{ route('accounting.paye-tables.activate', $table) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('accounting.paye-tables.edit', $table) }}" class="text-ink hover:text-gold">Edit</a>
                                        @if(!$table->is_current)
                                            <form method="POST" action="{{ route('accounting.paye-tables.destroy', $table) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this PAYE table?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">No PAYE tax tables found. Create one to get started.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
