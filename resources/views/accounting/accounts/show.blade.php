<x-app-layout>
    <x-slot name="header">{{ __('Account Detail') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" href="{{ route('accounting.accounts.edit', $account) }}">{{ __('Edit') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.accounts.index') }}">{{ __('Back to Accounts') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $account->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $account->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($account->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Sub Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($account->sub_type)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($account->is_active)
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill neutral">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Currency') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $account->currency }}</dd>
                    </div>
                    @if($account->parent)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Parent Account') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('accounting.accounts.show', $account->parent) }}" class="text-ink hover:text-gold">
                                    {{ $account->parent->code }} - {{ $account->parent->name }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($account->description)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $account->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Balance') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Opening Balance') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ format_money($account->opening_balance) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Opening Balance Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $account->opening_balance_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="col-span-2 border-t pt-4">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Current Balance') }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ format_money($account->current_balance) }}</dd>
                    </div>
                </div>
            </div>

            @if($account->children->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Child Accounts') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th class="text-right">Balance</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($account->children as $child)
                                    <tr>
                                        <td>
                                            <a href="{{ route('accounting.accounts.show', $child) }}" class="text-ink hover:text-gold">
                                                {{ $child->code }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('accounting.accounts.show', $child) }}" class="hover:text-indigo-600">
                                                {{ $child->name }}
                                            </a>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
