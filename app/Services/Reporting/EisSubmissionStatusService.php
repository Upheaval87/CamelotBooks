<?php

namespace App\Services\Reporting;

use App\Models\EisSubmission;

class EisSubmissionStatusService
{
    public function generate(int $companyId): array
    {
        $submissions = EisSubmission::where('company_id', $companyId)
            ->with('terminal')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'receipt_number' => $s->receipt_number,
                'invoice_type' => $s->invoice_type,
                'status' => $s->status,
                'terminal_site_id' => $s->terminal->site_id ?? 'N/A',
                'retry_count' => $s->retry_count,
                'error_message' => $s->error_message,
                'submitted_at' => $s->submitted_at?->format('Y-m-d H:i:s'),
                'accepted_at' => $s->accepted_at?->format('Y-m-d H:i:s'),
                'created_at' => $s->created_at?->format('Y-m-d H:i:s'),
            ])->toArray();

        $statusCounts = collect($submissions)->groupBy('status')->map->count()->toArray();
        $pendingRetry = collect($submissions)->where('status', 'error')->where('retry_count', '<', 5)->count();

        return [
            'submissions' => $submissions,
            'status_counts' => $statusCounts,
            'pending_retry' => $pendingRetry,
            'total' => count($submissions),
        ];
    }
}
