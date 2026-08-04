<x-app-layout>
    <x-list-header title="{{ __('Create Account') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.accounts.create') }}">
                    {{ __('Create Account') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.accounts.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <div class="scoped-search-field mt-1">
                            <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Code or name..." autocomplete="off" />
                            <span class="scoped-search-divider" aria-hidden="true"></span>
                            <button type="button" class="scoped-search-open" title="{{ __('Search this list') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: 'account' } }))">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1">
                        <x-input-label for="type" value="{{ __('Account Type') }}" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Types</option>
                            @foreach($typeLabels as $value => $label)
                                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('type'))
                            <a href="{{ route('accounting.accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            @forelse($typeLabels as $type => $label)
                @if($grouped->has($type))
                    <div class="mb-6 datasheet-wrap">
                        <div class="card-header">
                            <h3 class="text-base font-semibold text-ink">{{ $label }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="datasheet">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Sub Type</th>
                                        <th class="text-right">Balance</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grouped[$type]->sortBy('code') as $account)
                                        <tr class="{{ $account->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                            <td>
                                                <a href="{{ route('accounting.accounts.show', $account) }}" class="text-ink hover:text-gold">
                                                    {{ $account->code }}
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route('accounting.accounts.show', $account) }}" class="hover:text-indigo-600">
                                                    {{ $account->name }}
                                                </a>
                                            </td>
                                            <td class="text-ink-soft">
                                                {{ str_replace('_', ' ', ucfirst($account->sub_type)) }}
                                            </td>
                                            <td class="numeric">
                                                {{ format_money($account->current_balance) }}
                                            </td>
                                            <td class="text-center">
                                                @if($account->is_active)
                                                    <span class="status-pill positive">Active</span>
                                                @else
                                                    <span class="status-pill neutral">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('accounting.accounts.edit', $account) }}" class="text-ink hover:text-gold">Edit</a>
                                                <form method="POST" action="{{ route('accounting.accounts.toggle', $account) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-{{ $account->is_active ? 'red' : 'green' }}-600 hover:text-{{ $account->is_active ? 'red' : 'green' }}-900">
                                                        {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @foreach($accounts->where('parent_id', $account->id)->sortBy('code') as $child)
                                            <tr class="{{ $child->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                                <td>
                                                    <a href="{{ route('accounting.accounts.show', $child) }}" class="text-ink hover:text-gold">
                                                        {{ $child->code }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="text-gray-400 mr-1">└</span>
                                                    <a href="{{ route('accounting.accounts.show', $child) }}" class="hover:text-indigo-600">
                                                        {{ $child->name }}
                                                    </a>
                                                </td>
                                                <td class="text-ink-soft">
                                                    {{ str_replace('_', ' ', ucfirst($child->sub_type)) }}
                                                </td>
                                                <td class="numeric">
                                                    {{ format_money($child->current_balance) }}
                                                </td>
                                                <td class="text-center">
                                                    @if($child->is_active)
                                                        <span class="status-pill positive">Active</span>
                                                    @else
                                                        <span class="status-pill neutral">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    <a href="{{ route('accounting.accounts.edit', $child) }}" class="text-ink hover:text-gold">Edit</a>
                                                    <form method="POST" action="{{ route('accounting.accounts.toggle', $child) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-{{ $child->is_active ? 'red' : 'green' }}-600 hover:text-{{ $child->is_active ? 'red' : 'green' }}-900">
                                                            {{ $child->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    No accounts found.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
