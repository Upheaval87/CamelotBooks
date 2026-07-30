<form method="POST" action="{{ route('system-settings.update-notifications') }}">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">07 · EMAIL CONTENT</div>
        <div class="settings-section-title">Business Email Content</div>
        <p class="settings-section-desc">Configure the display name, footer, and signature used in outbound emails. SMTP server settings are managed separately by the system administrator.</p>
        <hr class="settings-section-divider">
    </div>

    <div class="settings-card">
        <div class="settings-grid">
            <x-settings.field label="Sender Display Name" name="sender_display_name" type="text" :value="$notifications['sender_display_name'] ?? ''" placeholder="e.g. Camelot Books Accounts" hint="The friendly name recipients see when they receive emails from the system." />
        </div>
        <x-settings.field label="Email Footer" name="email_footer" type="textarea" :value="$notifications['email_footer'] ?? ''" placeholder="This email was sent by Camelot Books Accounting System." hint="Appended to the bottom of all outbound emails." />
        <x-settings.field label="Email Signature" name="email_signature" type="textarea" :value="$notifications['email_signature'] ?? ''" placeholder="Kind regards,&#10;The Accounts Team" hint="Appended to the body of outbound emails before the footer." />
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save Email Content</button>
    </div>
</form>

<div class="settings-section-header mt-8">
    <div class="settings-section-eyebrow">08 · EMAIL TEMPLATES</div>
    <div class="settings-section-title">Email Templates</div>
    <p class="settings-section-desc">Overview of notification templates. Enable, disable, and customize templates from the full editor.</p>
    <hr class="settings-section-divider">
</div>

<div class="settings-card">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-ink-soft">Manage individual template content and settings.</p>
        <a href="{{ route('admin.notifications.index') }}" class="settings-pill-btn">Manage Templates</a>
    </div>

    <div class="settings-table-wrapper">
        <table class="settings-table">
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
                    @php $tpl = $emailTemplates->firstWhere('event_type', $eventType); @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-ink-soft">{{ $tpl->subject ?? '—' }}</td>
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
                        <td colspan="4" class="settings-table-empty">No email templates configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
