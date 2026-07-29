<x-app-layout>
    <x-slot name="header">{{ __('Edit User') }}</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit User: {{ $user->name }}</h1>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 transition ease-in-out duration-150">Back to Users</a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Role Assignment</h2>
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
                </div>

                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700">Company Role</label>
                    <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @foreach(['system_admin', 'company_admin', 'accountant', 'approver', 'viewer'] as $role)
                            <option value="{{ $role }}" {{ $pivot->role === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        <strong>system_admin:</strong> Full access across all companies<br>
                        <strong>company_admin:</strong> Full access within this company<br>
                        <strong>accountant:</strong> Accounting operations<br>
                        <strong>approver:</strong> Can approve journal entries, bills, etc.<br>
                        <strong>viewer:</strong> Read-only access
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">Update Role</button>
                </div>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Two-Factor Authentication</h2>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-900">Status: <span class="font-medium {{ $user->two_factor_enabled ? 'text-green-600' : 'text-gray-500' }}">{{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}</span></p>
                </div>
                <form method="POST" action="{{ route('admin.users.toggle-2fa', $user) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition ease-in-out duration-150">
                        {{ $user->two_factor_enabled ? 'Disable 2FA' : 'Enable 2FA' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
