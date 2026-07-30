@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Customers') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Customers" createRoute="{{ route('accounting.customers.create') }}" createLabel="Create Customer" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.customers.index') }}" searchPlaceholder="Name or email...">
                        <select name="status" class="list-filter-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </x-list-filter-bar>

                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
                    @endif

                    <div class="list-table-wrap">
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Terms</th>
                                    <th class="text-right">Balance ({{ $cs }})</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                <tr>
                                    <td>
                                        <div class="name-cell">
                                            <x-list-avatar-initials name="{{ $customer->name }}" size="sm" />
                                            <div class="name-cell-text">
                                                <span class="name-cell-primary"><a href="{{ route('accounting.customers.show', $customer) }}">{{ $customer->name }}</a></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-ink-soft">{{ $customer->email ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ $customer->phone ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ str_replace('_', ' ', ucfirst($customer->payment_terms ?? 'due_on_receipt')) }}</span></td>
                                    <td class="list-numeric">{{ format_number($customer->balance_due) }}</td>
                                    <td class="text-center">@if($customer->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.customers.show', $customer) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            <a href="{{ route('accounting.customers.edit', $customer) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span class="icon-btn-tooltip">Edit</span></a>
                                            <form method="POST" action="{{ route('accounting.customers.toggle', $customer) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $customer->is_active ? '19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' : '18 4l-4 4m0 0l-4-4m4 4V2m-4 6l4 4m-4-4H2' }}"/></svg><span class="icon-btn-tooltip">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-ink-soft py-8">No customers found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($customers->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $customers->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($customers as $customer)
                        <div class="list-mobile-card">
                            <div class="name-cell mb-2">
                                <x-list-avatar-initials name="{{ $customer->name }}" size="sm" />
                                <div class="name-cell-text">
                                    <span class="name-cell-primary"><a href="{{ route('accounting.customers.show', $customer) }}">{{ $customer->name }}</a></span>
                                </div>
                            </div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Email</span><span class="list-mobile-card-value">{{ $customer->email ?? '—' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Balance</span><span class="list-mobile-card-value">{{ format_number($customer->balance_due) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">@if($customer->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No customers found.</div>
                        @endforelse
                        @if($customers->hasPages())
                        <div class="px-2 py-3">{{ $customers->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Customers" :groups="[
                        [
                            ['route' => route('accounting.customers.index'), 'title' => 'All Customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                            ['route' => route('accounting.customers.index', ['status' => 'active']), 'title' => 'Active', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.customers.index', ['status' => 'inactive']), 'title' => 'Inactive', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.customers.create'), 'title' => 'Create Customer', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'Add new record'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Customer Balances', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['route' => '#', 'title' => 'Sales by Customer', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
