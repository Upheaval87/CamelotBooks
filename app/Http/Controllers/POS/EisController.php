<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EisSubmission;
use App\Models\EisTerminal;
use App\Services\EIS\EisSubmissionService;
use Illuminate\Http\Request;

class EisController extends Controller
{
    protected EisSubmissionService $service;

    public function __construct(EisSubmissionService $service)
    {
        $this->service = $service;
    }

    public function terminals()
    {
        $companyId = session('current_company_id');

        $terminals = EisTerminal::where('company_id', $companyId)
            ->withCount('submissions')
            ->orderByDesc('created_at')
            ->get();

        return view('pos.eis.terminals', compact('terminals'));
    }

    public function storeTerminal(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'site_id' => 'required|string|max:50',
            'device_serial' => 'nullable|string|max:100',
        ]);

        $validated['company_id'] = $companyId;
        $validated['status'] = EisTerminal::STATUS_PENDING;

        $terminal = EisTerminal::create($validated);

        AuditLog::log(
            $companyId,
            $request->user()->id,
            EisTerminal::class,
            $terminal->id,
            'pos.eis.terminal.registered',
            null,
            ['site_id' => $terminal->site_id, 'device_serial' => $terminal->device_serial],
            "EIS Terminal {$terminal->site_id} registered"
        );

        return redirect()->route('pos.eis.terminals')
            ->with('success', 'Terminal registered. Activate it with a TAC from MRA portal.');
    }

    public function activateTerminal(Request $request, EisTerminal $terminal)
    {
        if ($terminal->company_id !== session('current_company_id')) {
            abort(404);
        }

        $validated = $request->validate([
            'tac' => 'required|string|max:50',
        ]);

        try {
            $this->service->activateTerminal($terminal, $validated['tac']);

            AuditLog::log(
                session('current_company_id'),
                $request->user()->id,
                EisTerminal::class,
                $terminal->id,
                'pos.eis.terminal.activated',
                ['status' => EisTerminal::STATUS_PENDING],
                ['status' => EisTerminal::STATUS_ACTIVE],
                "EIS Terminal {$terminal->site_id} activated"
            );

            return redirect()->route('pos.eis.terminals')
                ->with('success', "Terminal {$terminal->site_id} activated successfully.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('pos.eis.terminals')
                ->with('error', $e->getMessage());
        }
    }

    public function submissions()
    {
        $companyId = session('current_company_id');

        $submissions = EisSubmission::where('company_id', $companyId)
            ->with('terminal')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('pos.eis.submissions', compact('submissions'));
    }

    public function retrySubmission(EisSubmission $submission)
    {
        if ($submission->company_id !== session('current_company_id')) {
            abort(404);
        }

        try {
            $result = $this->service->retrySubmission($submission);

            AuditLog::log(
                session('current_company_id'),
                $request->user()->id,
                EisSubmission::class,
                $submission->id,
                'pos.eis.submission.retried',
                ['status' => $submission->status],
                ['status' => $result->status],
                "EIS Submission {$submission->id} retried – {$result->status}"
            );

            if ($result->status === EisSubmission::STATUS_ACCEPTED) {
                return redirect()->back()->with('success', 'Submission accepted by MRA.');
            }

            return redirect()->back()->with('error', 'Submission rejected: ' . ($result->error_message ?? 'Unknown'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
