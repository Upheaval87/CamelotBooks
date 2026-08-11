<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'notifications'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="notifications-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Email Content') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-notifications') }}" id="notifications-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span>
                <h2>{{ __('Business Email Content') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Configure the display name, footer, and signature used in outbound emails. SMTP server settings are managed separately by the system administrator.</p>

            <div class="g3">
                <x-settings.field label="Sender Display Name" name="sender_display_name" type="text" :value="$notifications['sender_display_name'] ?? ''" placeholder="e.g. Camelot Books Accounts" hint="The friendly name recipients see when they receive emails from the system." />
            </div>
            <div class="g2">
                <x-settings.field label="Email Footer" name="email_footer" type="textarea" :value="$notifications['email_footer'] ?? ''" placeholder="This email was sent by Camelot Books Accounting System." hint="Appended to the bottom of all outbound emails." />
                <x-settings.field label="Email Signature" name="email_signature" type="textarea" :value="$notifications['email_signature'] ?? ''" placeholder="Kind regards,&#10;The Accounts Team" hint="Appended to the body of outbound emails before the footer." />
            </div>
        </div>

        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <h2>{{ __('Email Templates') }}</h2>
                <div class="rule"></div>
                <a href="{{ route('admin.notifications.index') }}" class="btn ghost sm">{{ __('Manage Templates') }}</a>
            </div>
            <p class="sub">Overview of notification templates. Enable, disable, and customize templates from the full editor.</p>

            <div class="li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Event') }}</th>
                            <th>{{ __('Subject') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Scope') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventLabels as $eventType => $label)
                            @php $tpl = $emailTemplates->firstWhere('event_type', $eventType); @endphp
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="em">{{ $tpl->subject ?? '—' }}</td>
                                <td>
                                    @if($tpl && $tpl->is_enabled)
                                        <span class="badge b-act"><span class="bdot"></span>{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge b-gray"><span class="bdot"></span>{{ __('Disabled') }}</span>
                                    @endif
                                </td>
                                <td class="em">
                                    @if($tpl && $tpl->company_id)
                                        {{ __('Company') }}
                                    @else
                                        {{ __('System Default') }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">No email templates configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
