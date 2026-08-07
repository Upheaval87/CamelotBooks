<x-app-layout>
    <x-list-header title="{{ __('Edit User') }}" />

<div class="py-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="form-page">
            <div class="form-page-main">
                

                <div class="card p-6 mb-6">
                    <x-form.section number="01" title="Role Assignment" />
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-input-label value="Email" />
                            <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="role" value="Company Role" />
                            <select name="role" id="role" class="input mt-1">
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

                        <div class="flex justify-end mt-8 gap-3">
                            <x-button variant="ghost" href="{{ route('admin.users.index') }}">{{ __('Cancel') }}</x-button>
                            <x-primary-button type="submit">{{ __('Update Role') }}</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="card p-6">
                    <x-form.section number="02" title="Two-Factor Authentication" />
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-900">Status: <span class="font-medium {{ $user->two_factor_enabled ? 'text-green-600' : 'text-gray-500' }}">{{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}</span></p>
                        </div>
                        <form method="POST" action="{{ route('admin.users.toggle-2fa', $user) }}">
                            @csrf
                            <x-button variant="ghost" type="submit">{{ $user->two_factor_enabled ? 'Disable 2FA' : 'Enable 2FA' }}</x-button>
                        </form>
                    </div>
                </div>
            </div>

            <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                ['label' => __('View'), 'links' => [
                    ['title' => __('Users List'), 'route' => route('admin.users.index'), 'icon' => 'table-list'],
                ]],
            ]" />
        </div>
    </div>
</div>
</x-app-layout>
