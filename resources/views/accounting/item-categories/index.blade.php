<x-app-layout>
    <x-list-header title="{{ __('New Category') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.item-categories.create') }}">
                    {{ __('New Category') }}
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
                                <th>Code</th>
                                <th>Name</th>
                                <th>Default Income Account</th>
                                <th>Default COGS Account</th>
                                <th class="text-right">Products</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr class="hover:bg-gray-50">
                                    <td class="text-ink-soft">{{ $category->code }}</td>
                                    <td>
                                        <a href="{{ route('accounting.item-categories.show', $category) }}" class="text-ink hover:text-gold">
                                            {{ $category->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $category->defaultIncomeAccount->code ?? '' }} {{ $category->defaultIncomeAccount->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $category->defaultCogsAccount->code ?? '' }} {{ $category->defaultCogsAccount->name ?? '—' }}</td>
                                    <td class="numeric">{{ $category->products_count }}</td>
                                    <td class="text-center">
                                        @if($category->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill neutral">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('accounting.item-categories.edit', $category) }}" class="text-ink hover:text-gold">Edit</a>
                                        <form method="POST" action="{{ route('accounting.item-categories.toggle', $category) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-ink hover:text-gold">
                                                {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No item categories found. Create one to organize your products.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
