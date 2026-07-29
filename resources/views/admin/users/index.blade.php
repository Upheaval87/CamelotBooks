<x-app-layout>
    <x-slot name="header">{{ __('User Management') }}</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">User Management</h1>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
        @endif

        <div class="datasheet-wrap">
            <div class="overflow-x-auto">
                <table class="datasheet">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>2FA</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        @php $pivot = $user->companies->firstWhere('id', session('current_company_id'))?->pivot; @endphp
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    $roleClass = match($pivot->role) {
                                        'system_admin' => 'neutral',
                                        'company_admin' => 'neutral',
                                        'approver' => 'positive',
                                        'accountant' => 'neutral',
                                        default => 'neutral',
                                    };
                                @endphp
                                <span class="status-pill {{ $roleClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $pivot->role)) }}
                                </span>
                            </td>
                            <td>
                                @if($user->two_factor_enabled)
                                    <span class="status-pill positive">Enabled</span>
                                @else
                                    <span class="status-pill neutral">Disabled</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-ink hover:text-gold">Edit Role</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
