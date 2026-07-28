<form method="POST" action="{{ route('system-settings.update-notifications') }}">
    @csrf
    @method('PUT')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">SMTP Configuration</h3>
            <p class="mt-1 text-sm text-gray-600">Configure the outbound email server for this company. These settings are used when sending invoices, receipts, payslips, and other notifications.</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-gray-700">SMTP Host</label>
                    <input type="text" name="smtp_host" id="smtp_host" value="{{ $smtpSettings['host'] ?? '' }}"
                        placeholder="smtp.example.com"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>
                <div>
                    <label for="smtp_port" class="block text-sm font-medium text-gray-700">Port</label>
                    <input type="number" name="smtp_port" id="smtp_port" value="{{ $smtpSettings['port'] ?? '587' }}"
                        min="1" max="65535"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="smtp_username" id="smtp_username" value="{{ $smtpSettings['username'] ?? '' }}"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>
                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="smtp_password" id="smtp_password" value="{{ $smtpSettings['password'] ?? '' }}"
                        placeholder="{{ $smtpSettings['password'] ? '••••••••' : '' }}"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>
                <div>
                    <label for="smtp_encryption" class="block text-sm font-medium text-gray-700">Encryption</label>
                    <select name="smtp_encryption" id="smtp_encryption" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="tls" {{ ($smtpSettings['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($smtpSettings['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ ($smtpSettings['encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
                <div>
                    <label for="smtp_from_name" class="block text-sm font-medium text-gray-700">From Name</label>
                    <input type="text" name="smtp_from_name" id="smtp_from_name" value="{{ $smtpSettings['from_name'] ?? '' }}"
                        placeholder="Camelot Books"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>
                <div class="md:col-span-2">
                    <label for="smtp_from_address" class="block text-sm font-medium text-gray-700">From Address</label>
                    <input type="email" name="smtp_from_address" id="smtp_from_address" value="{{ $smtpSettings['from_address'] ?? '' }}"
                        placeholder="noreply@example.com"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Save SMTP Settings
            </button>
        </div>
    </div>
</form>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Email Templates</h3>
            <p class="mt-1 text-sm text-gray-600">Overview of notification templates. Enable, disable, and customize templates from the full editor.</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
            Manage Templates
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scope</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($eventLabels as $eventType => $label)
                    @php
                        $tpl = $emailTemplates->firstWhere('event_type', $eventType);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $label }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $tpl->subject ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($tpl && $tpl->is_enabled)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Disabled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @if($tpl && $tpl->company_id)
                                Company
                            @else
                                System Default
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            No email templates configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
