@php
    [$pill, $label] = match ($status) {
        \App\Models\BranchRequest::STATUS_PENDING_REVIEW => ['neutral', 'Pending Review'],
        \App\Models\BranchRequest::STATUS_QUOTED => ['neutral', 'Quoted'],
        \App\Models\BranchRequest::STATUS_AWAITING_PAYMENT => ['neutral', 'Awaiting Payment'],
        \App\Models\BranchRequest::STATUS_APPROVED => ['positive', 'Approved'],
        \App\Models\BranchRequest::STATUS_REJECTED => ['negative', 'Rejected'],
        \App\Models\BranchRequest::STATUS_EXPIRED => ['default', 'Expired'],
        \App\Models\BranchRequest::STATUS_CANCELLED => ['default', 'Cancelled'],
        default => ['default', ucwords(str_replace('_', ' ', $status))],
    };
@endphp
<span class="status-pill {{ $pill }}">{{ $label }}</span>
