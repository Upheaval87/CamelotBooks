<x-app-layout>
    <x-list-header title="{{ __('Notification Settings') }}" />

<div class="py-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Notification Settings</h1>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.notifications.update') }}">
            @csrf
            @method('PUT')

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">SMTP Configuration</h2>
                <p class="text-sm text-gray-500 mb-4">Emails are sent synchronously (no queue). Keep templates lightweight for best performance.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
                        <input type="text" name="smtp[host]" value="{{ $smtpSettings['host'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="smtp.gmail.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Port</label>
                        <input type="number" name="smtp[port]" value="{{ $smtpSettings['port'] ?? '587' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="smtp[username]" value="{{ $smtpSettings['username'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="smtp[password]" value="{{ $smtpSettings['password'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Encryption</label>
                        <select name="smtp[encryption]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="tls" {{ ($smtpSettings['encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($smtpSettings['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="none" {{ ($smtpSettings['encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Address</label>
                        <input type="email" name="smtp[from_address]" value="{{ $smtpSettings['from_address'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="noreply@company.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Name</label>
                        <input type="text" name="smtp[from_name]" value="{{ $smtpSettings['from_name'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="My Company">
                    </div>
                </div>
            </div>

            <div class="flex justify-end mb-6">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                    Save SMTP Settings
                </button>
            </div>
        </form>

        <div class="datasheet-wrap">
            <div class="card-header">
                <h2 class="text-base font-semibold text-ink">Email Templates</h2>
                <p class="text-sm text-ink-soft mt-1">Available variables are shown in double curly braces. Keep templates lightweight — emails are sent synchronously.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="datasheet">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventLabels as $key => $label)
                        @php $tpl = $templates->firstWhere('event_type', $key); @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                @if($tpl && $tpl->is_enabled)
                                    <span class="status-pill positive">Enabled</span>
                                @else
                                    <span class="status-pill neutral">Disabled</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.notifications.template-edit', $key) }}" class="text-ink hover:text-gold">Edit Template</a>
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
