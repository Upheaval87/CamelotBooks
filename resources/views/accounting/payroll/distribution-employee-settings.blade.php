<x-app-layout>
    <div class="pd max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        <nav class="pd-crumbs">
            <a href="{{ route('accounting.payroll.dashboard') }}">Payroll</a> ›
            <span class="here">Employee Payslip Settings</span>
        </nav>

        <div class="pd-page-head">
            <div>
                <h1>Employee Payslip Settings</h1>
                <div class="pd-sub">Configure email delivery and portal access for each employee.</div>
            </div>
        </div>

        <div class="pd-card">
            <div class="pd-table-wrap">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee #</th>
                            <th>Email</th>
                            <th>Email Delivery</th>
                            <th>Portal Access</th>
                            <th>Custom Email</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $s = $settings->get($emp->id);
                            @endphp
                            <tr>
                                <td class="pd-bold">{{ $emp->full_name }}</td>
                                <td class="pd-mono">{{ $emp->employee_number ?? '—' }}</td>
                                <td class="pd-mono pd-em">{{ $emp->email ?? '—' }}</td>
                                <td>
                                    @if($s && $s->email_delivery)
                                        <span class="pd-badge pd-badge-active">Enabled</span>
                                    @else
                                        <span class="pd-badge pd-badge-muted">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($s && $s->portal_access)
                                        <span class="pd-badge pd-badge-active">Enabled</span>
                                    @else
                                        <span class="pd-badge pd-badge-muted">Disabled</span>
                                    @endif
                                </td>
                                <td class="pd-mono pd-em">{{ $s?->custom_email ?? '—' }}</td>
                                <td class="pd-actions">
                                    <button type="button" class="pd-ibtn pd-ibtn-edit"
                                        onclick="toggleEdit({{ $emp->id }}, {{ $s?->email_delivery ? 'true' : 'false' }}, {{ $s?->portal_access ? 'true' : 'false' }}, '{{ addslashes($s?->custom_email ?? '') }}')"
                                        title="Edit Settings">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="pd-empty">No active employees.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Edit modal --}}
        <div id="edit-modal" class="pd-modal" style="display:none">
            <div class="pd-modal-card">
                <h3>Edit Payslip Settings</h3>
                <form method="POST" action="{{ route('accounting.payroll.distribution.update-employee-settings') }}">
                    @csrf
                    <input type="hidden" name="employee_id" id="modal-employee-id">
                    <div class="pd-field">
                        <label class="pd-label">Email Delivery</label>
                        <label class="pd-toggle">
                            <input type="hidden" name="email_delivery" value="0">
                            <input type="checkbox" name="email_delivery" value="1" id="modal-email-delivery">
                            <span class="pd-toggle-track"><span class="pd-toggle-thumb"></span></span>
                            <span class="pd-toggle-text">Send payslips via email</span>
                        </label>
                    </div>
                    <div class="pd-field">
                        <label class="pd-label">Portal Access</label>
                        <label class="pd-toggle">
                            <input type="hidden" name="portal_access" value="0">
                            <input type="checkbox" name="portal_access" value="1" id="modal-portal-access">
                            <span class="pd-toggle-track"><span class="pd-toggle-thumb"></span></span>
                            <span class="pd-toggle-text">Allow self-service portal access</span>
                        </label>
                    </div>
                    <div class="pd-field">
                        <label class="pd-label">Custom Email (optional)</label>
                        <input type="email" name="custom_email" id="modal-custom-email" class="pd-input" placeholder="Override employee email">
                    </div>
                    <div class="pd-modal-actions">
                        <button type="button" class="pd-btn pd-btn-ghost" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="pd-btn pd-btn-cta">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleEdit(id, emailDelivery, portalAccess, customEmail) {
            document.getElementById('modal-employee-id').value = id;
            document.getElementById('modal-email-delivery').checked = emailDelivery;
            document.getElementById('modal-portal-access').checked = portalAccess;
            document.getElementById('modal-custom-email').value = customEmail;
            document.getElementById('edit-modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('edit-modal').style.display = 'none';
        }
    </script>
    @endpush
</x-app-layout>
