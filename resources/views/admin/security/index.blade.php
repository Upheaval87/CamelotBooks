<x-app-layout>
    <x-list-header title="{{ __('Security Settings') }}" />

<div class="py-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Security Settings</h1>

        

        <form method="POST" action="{{ route('admin.security.update') }}">
            @csrf
            @method('PUT')

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Password Policy</h2>
                <p class="text-sm text-gray-500 mb-4">Enforced at the authentication layer — cannot be bypassed by custom forms.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Minimum Length</label>
                        <input type="number" name="password[min_length]" value="{{ $settings['password.min_length'] ?? '8' }}" min="4" max="128" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password Expiry (days)</label>
                        <input type="number" name="password[expiry_days]" value="{{ $settings['password.expiry_days'] ?? '0' }}" min="0" max="365" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">0 = never expires</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password History</label>
                        <input type="number" name="password[history_count]" value="{{ $settings['password.history_count'] ?? '0' }}" min="0" max="24" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Prevent reuse of last N passwords. 0 = disabled</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="password[require_uppercase]" value="0">
                        <input type="checkbox" name="password[require_uppercase]" value="1" {{ ($settings['password.require_uppercase'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500">
                        <span class="text-sm text-gray-700">Uppercase</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="password[require_lowercase]" value="0">
                        <input type="checkbox" name="password[require_lowercase]" value="1" {{ ($settings['password.require_lowercase'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500">
                        <span class="text-sm text-gray-700">Lowercase</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="password[require_number]" value="0">
                        <input type="checkbox" name="password[require_number]" value="1" {{ ($settings['password.require_number'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500">
                        <span class="text-sm text-gray-700">Numbers</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="password[require_symbol]" value="0">
                        <input type="checkbox" name="password[require_symbol]" value="1" {{ ($settings['password.require_symbol'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500">
                        <span class="text-sm text-gray-700">Symbols</span>
                    </label>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Session & Login Protection</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Session Timeout (minutes)</label>
                        <input type="number" name="session[timeout_minutes]" value="{{ $settings['session.timeout_minutes'] ?? '120' }}" min="5" max="480" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Max Failed Login Attempts</label>
                        <input type="number" name="login[max_attempts]" value="{{ $settings['login.max_attempts'] ?? '5' }}" min="1" max="20" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lockout Duration (minutes)</label>
                        <input type="number" name="login[lockout_minutes]" value="{{ $settings['login.lockout_minutes'] ?? '15' }}" min="1" max="1440" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Two-Factor Authentication</h2>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="tfa[require_for_admins]" value="0">
                    <input type="checkbox" name="tfa[require_for_admins]" value="1" {{ ($settings['tfa.require_for_admins'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500">
                    <span class="text-sm text-gray-700">Require 2FA for users with admin roles (system_admin, company_admin)</span>
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                    Save Security Settings
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
