<form method="POST" action="{{ route('system-settings.update-notifications') }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="form-section-label">1 · Business Email Content</div>
            <p class="mt-1 text-sm text-ink-soft">Configure the display name, footer, and signature used in outbound emails. SMTP server settings are managed separately by the system administrator.</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="sender_display_name" class="block text-sm font-medium text-gray-700">Sender Display Name</label>
                    <input type="text" name="sender_display_name" id="sender_display_name" value="{{ $notifications['sender_display_name'] ?? '' }}"
                        placeholder="e.g. Camelot Books Accounts"
                        class="input" />
                    <p class="mt-1 text-xs text-gray-500">The friendly name recipients see when they receive emails from the system.</p>
                </div>
            </div>
            <div>
                <label for="email_footer" class="block text-sm font-medium text-gray-700">Email Footer</label>
                <textarea name="email_footer" id="email_footer" rows="3"
                    placeholder="This email was sent by Camelot Books Accounting System."
                    class="input">{{ $notifications['email_footer'] ?? '' }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Appended to the bottom of all outbound emails.</p>
            </div>
            <div>
                <label for="email_signature" class="block text-sm font-medium text-gray-700">Email Signature</label>
                <textarea name="email_signature" id="email_signature" rows="3"
                    placeholder="Kind regards,&#10;The Accounts Team"
                    class="input">{{ $notifications['email_signature'] ?? '' }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Appended to the body of outbound emails before the footer.</p>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <x-button variant="primary" type="submit">Save Email Content</x-button>
        </div>
    </div>
</form>

<div class="card mt-6">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <div class="form-section-label">2 · Email Templates</div>
            <p class="mt-1 text-sm text-ink-soft">Overview of notification templates. Enable, disable, and customize templates from the full editor.</p>
        </div>
        <x-button variant="ghost" href="{{ route('admin.notifications.index') }}">Manage Templates</x-button>
    </div>
    <div class="overflow-x-auto">
        <table class="datasheet">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Scope</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventLabels as $eventType => $label)
                    @php
                        $tpl = $emailTemplates->firstWhere('event_type', $eventType);
                    @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-ink-soft">
                            {{ $tpl->subject ?? '—' }}
                        </td>
                        <td>
                            @if($tpl && $tpl->is_enabled)
                                <span class="status-pill positive">Enabled</span>
                            @else
                                <span class="status-pill negative">Disabled</span>
                            @endif
                        </td>
                        <td class="text-ink-soft">
                            @if($tpl && $tpl->company_id)
                                Company
                            @else
                                System Default
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-ink-soft text-center">
                            No email templates configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
