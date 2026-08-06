<x-app-layout>

    <div class="sa-page py-6" style="background: #F8F9FC;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Branch Requests') }}</h1>
                    <p class="sa-page-subtitle">{{ __('Requests for extra branch capacity, with quotations and payments.') }}</p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4" style="padding: 12px 16px; border-radius: 10px; background: #eef6ee; border: 1px solid #cfe6cf; color: #22662c; font-size: 13px;">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4" style="padding: 12px 16px; border-radius: 10px; background: #fbecec; border: 1px solid #f2c9c9; color: #8e3b3b; font-size: 13px;">{{ $errors->first() }}</div>
            @endif

            <x-elevated-card :flush="true">
                <div class="sa-table-wrap">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th class="sa-table-num">{{ __('Qty') }}</th>
                                <th>{{ __('Requested') }}</th>
                                <th>{{ __('Quotation') }}</th>
                                <th class="sa-table-center">{{ __('Status') }}</th>
                                <th class="sa-table-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $pillMap = [
                                    \App\Models\BranchRequest::STATUS_PENDING_REVIEW => 'sa-pill--amber',
                                    \App\Models\BranchRequest::STATUS_QUOTED => 'sa-pill--accent',
                                    \App\Models\BranchRequest::STATUS_AWAITING_PAYMENT => 'sa-pill--amber',
                                    \App\Models\BranchRequest::STATUS_APPROVED => 'sa-pill--accent',
                                    \App\Models\BranchRequest::STATUS_REJECTED => 'sa-pill--danger',
                                    \App\Models\BranchRequest::STATUS_EXPIRED => 'sa-pill--muted',
                                    \App\Models\BranchRequest::STATUS_CANCELLED => 'sa-pill--muted',
                                ];
                            @endphp
                            @forelse($requests as $r)
                                @php $label = $r->statusLabel(); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.companies.branch-requests.show', [$r->company_id, $r->id]) }}" class="sa-table-primary">{{ $r->company_name ?? ($r->company?->name ?? '—') }}</a>
                                    </td>
                                    <td>
                                        <span style="color: var(--sa-muted);">{{ $r->branch_name }}</span>
                                        @if($r->branch_code)
                                            <span class="sa-table-sub">{{ $r->branch_code }}</span>
                                        @endif
                                    </td>
                                    <td class="sa-table-num" style="font-weight: 600;">{{ $r->requested_quantity }}</td>
                                    <td><span style="color: var(--sa-muted);">{{ $r->requested_at?->format('M j, Y') }}</span></td>
                                    <td>
                                        @if($r->quotation)
                                            <span class="sa-table-mono">{{ $r->quotation->quotation_number }}</span>
                                            <span class="sa-table-sub">{{ number_format($r->quotation->total, 2) }} {{ $r->quotation->currency_code }}</span>
                                        @else
                                            <span style="color: #c8ccd2;">—</span>
                                        @endif
                                    </td>
                                    <td class="sa-table-center">
                                        <span class="sa-pill {{ $pillMap[$r->status] ?? 'sa-pill--muted' }}">{{ $label }}</span>
                                    </td>
                                    <td class="sa-table-center">
                                        <a href="{{ route('superadmin.companies.branch-requests.show', [$r->company_id, $r->id]) }}" class="sa-btn sa-btn--tint">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="sa-table-empty">{{ __('No branch requests found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-elevated-card>
        </div>
    </div>
</x-app-layout>
