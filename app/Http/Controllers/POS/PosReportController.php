<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosCashierSession;
use App\Services\POS\PosReportService;
use Illuminate\Http\Request;

class PosReportController extends Controller
{
    public function xReport(Request $request)
    {
        $companyId = session('current_company_id');
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            $openSession = PosCashierSession::where('company_id', $companyId)
                ->where('status', 'open')
                ->latest()
                ->first();

            if ($openSession) {
                $sessionId = $openSession->id;
            } else {
                $latestSession = PosCashierSession::where('company_id', $companyId)
                    ->latest()
                    ->first();

                if ($latestSession) {
                    return redirect()->route('pos.reports.x-report', ['session_id' => $latestSession->id]);
                }

                return view('pos.reports.x-report', ['data' => null]);
            }
        }

        $data = app(PosReportService::class)->xReport($companyId, (int) $sessionId);

        return view('pos.reports.x-report', compact('data'));
    }

    public function zReport(Request $request)
    {
        $companyId = session('current_company_id');
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            $latestClosed = PosCashierSession::where('company_id', $companyId)
                ->where('status', 'closed')
                ->latest()
                ->first();

            if ($latestClosed) {
                return redirect()->route('pos.reports.z-report', ['session_id' => $latestClosed->id]);
            }

            return view('pos.reports.z-report', ['data' => null]);
        }

        $data = app(PosReportService::class)->zReport($companyId, (int) $sessionId);

        return view('pos.reports.z-report', compact('data'));
    }

    public function salesByTerminal(Request $request)
    {
        $companyId = session('current_company_id');

        $from = $request->query('from');
        $to = $request->query('to');

        $data = app(PosReportService::class)->salesByTerminal($companyId, $from, $to);

        return view('pos.reports.sales-by-terminal', compact('data'));
    }

    public function salesByCashier(Request $request)
    {
        $companyId = session('current_company_id');

        $from = $request->query('from');
        $to = $request->query('to');

        $data = app(PosReportService::class)->salesByCashier($companyId, $from, $to);

        return view('pos.reports.sales-by-cashier', compact('data'));
    }
}
