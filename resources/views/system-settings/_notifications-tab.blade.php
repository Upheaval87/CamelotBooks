<form method="POST" action="{{ route('system-settings.update-notifications') }}">
    @csrf
    @method('PUT')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Business Email Content</h3>
            <p class="mt-1 text-sm text-gray-600">Configure the display name, footer, and signature used in outbound emails. SMTP server settings are managed separately by the system administrator.</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="sender_display_name" class="block text-sm font-medium text-gray-700">Sender Display Name</label>
                    <input type="text" name="sender_display_name" id="sender_display_name" value="{{ $notifications['sender_display_name'] ?? '' }}"
                        placeholder="e.g. Camelot Books Accounts"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    <p class="mt-1 text-xs text-gray-500">The friendly name recipients see when they receive emails from the system.</p>
                </div>
            </div>
            <div>
                <label for="email_footer" class="block text-sm font-medium text-gray-700">Email Footer</label>
                <textarea name="email_footer" id="email_footer" rows="3"
                    placeholder="This email was sent by Camelot Books Accounting System."
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $notifications['email_footer'] ?? '' }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Appended to the bottom of all outbound emails.</p>
            </div>
            <div>
                <label for="email_signature" class="block text-sm font-medium text-gray-700">Email Signature</label>
                <textarea name="email_signature" id="email_signature" rows="3"
                    placeholder="Kind regards,&#10;The Accounts Team"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $notifications['email_signature'] ?? '' }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Appended to the body of outbound emails before the footer.</p>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                Save Email Content
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
