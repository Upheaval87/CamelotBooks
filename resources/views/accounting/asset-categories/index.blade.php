<x-app-layout>
    <x-slot name="header">{{ __('Create Category') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.asset-categories.create') }}">
                    {{ __('Create Category') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @forelse($categories as $category)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Financial Method</th>
                                    <th>Useful Life (Fin)</th>
                                    <th>Tax Method</th>
                                    <th>Useful Life (Tax)</th>
                                    <th class="text-center">Revaluation</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $cat)
                                    <tr class="{{ $cat->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                        <td>
                                            <a href="{{ route('accounting.asset-categories.show', $cat) }}" class="text-ink hover:text-gold">
                                                {{ $cat->code }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('accounting.asset-categories.show', $cat) }}" class="hover:text-indigo-600">
                                                {{ $cat->name }}
                                            </a>
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ str_replace('_', ' ', ucfirst($cat->depreciation_method_financial)) }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $cat->useful_life_financial }} yrs
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ str_replace('_', ' ', ucfirst($cat->depreciation_method_tax)) }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $cat->useful_life_tax }} yrs
                                        </td>
                                        <td class="text-center">
                                            @if($cat->is_revaluation_enabled)
                                                <span class="status-pill neutral">Enabled</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">Disabled</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($cat->is_active)
                                                <span class="status-pill positive">Active</span>
                                            @else
                                                <span class="status-pill neutral">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('accounting.asset-categories.show', $cat) }}" class="text-ink hover:text-gold">View</a>
                                            <a href="{{ route('accounting.asset-categories.edit', $cat) }}" class="text-ink hover:text-gold">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    No asset categories found.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
