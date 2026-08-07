<x-app-layout>
    <x-list-header title="{{ __('Account Detail') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.accounts.edit', $account) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.accounts.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Accounts') }}
                </a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Code') }}" strong>{{ $account->code }}</x-detail-field>
                            <x-detail-field label="{{ __('Name') }}" strong>{{ $account->name }}</x-detail-field>
                            <x-detail-field label="{{ __('Type') }}">{{ ucfirst($account->type) }}</x-detail-field>
                            <x-detail-field label="{{ __('Sub Type') }}">{{ str_replace('_', ' ', ucfirst($account->sub_type)) }}</x-detail-field>
                            <x-detail-field label="{{ __('Status') }}" noBorder>
                                @if($account->is_active)
                                    <span class="status-pill positive">{{ __('Active') }}</span>
                                @else
                                    <span class="status-pill neutral">{{ __('Inactive') }}</span>
                                @endif
                            </x-detail-field>
                            <x-detail-field label="{{ __('Currency') }}">{{ $account->currency }}</x-detail-field>
                            @if($account->parent)
                                <x-detail-field label="{{ __('Parent Account') }}">
                                    <a href="{{ route('accounting.accounts.show', $account->parent) }}" class="text-ink hover:text-gold">
                                        {{ $account->parent->code }} - {{ $account->parent->name }}
                                    </a>
                                </x-detail-field>
                            @endif
                            @if($account->description)
                                <x-detail-field label="{{ __('Description') }}">{{ $account->description }}</x-detail-field>
                            @endif
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Balance') }}</p>
                        <div class="balance-grid">
                            <x-detail-field label="{{ __('Opening Balance') }}">{{ format_money($account->opening_balance) }}</x-detail-field>
                            <x-detail-field label="{{ __('Opening Balance Date') }}">{{ $account->opening_balance_date?->format('M d, Y') ?? '—' }}</x-detail-field>
                        </div>
                        <div class="balance-total-row">
                            <p class="detail-lbl">{{ __('Current Balance') }}</p>
                            <span class="balance-amount">{{ format_money($account->current_balance) }}</span>
                        </div>
                    </div>

                    @if($account->children->count() > 0)
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('Child Accounts') }}</p>
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
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
                                                    <a href="{{ route('accounting.accounts.show', $child) }}" class="hover:text-gold-700">
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
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.accounts.show', $account), 'icon' => 'view', 'title' => __('View')],
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.accounts.index'), 'icon' => 'back', 'title' => __('Back to Chart of Accounts')],
                    ]],
                ]" />
            </div>

        </div>
    </div>
</x-app-layout>
