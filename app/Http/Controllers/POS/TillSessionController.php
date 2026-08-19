<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosCashierSession;
use App\Models\PosTerminal;
use App\Services\POS\TillSessionService;
use Illuminate\Http\Request;

class TillSessionController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $sessions = PosCashierSession::where('company_id', $companyId)
            ->with(['terminal', 'user'])
            ->latest()
            ->paginate(25);

        $terminals = PosTerminal::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('identifier')
            ->get();

        return view('pos.till-sessions.index', compact('sessions', 'terminals'));
    }

    public function open(Request $request)
    {
        $companyId = session('current_company_id');

        $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
            'opening_float' => 'required|numeric|min:0',
        ]);

        $terminal = PosTerminal::where('id', $request->terminal_id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$terminal) {
            return back()->withErrors(['terminal_id' => 'Terminal not found or inactive.'])->withInput();
        }

        try {
            app(TillSessionService::class)->openTill(
                $companyId,
                $terminal->id,
                $request->user()->id,
                (float) $request->opening_float
            );

            return redirect()->route('pos.register.index')->with('success', 'Till opened successfully.');
        } catch (\LogicException $e) {
            return back()->withErrors(['terminal_id' => $e->getMessage()])->withInput();
        }
    }

    public function close(Request $request, PosCashierSession $session)
    {
        $companyId = session('current_company_id');

        abort_unless($session->company_id === $companyId, 403);
        abort_unless($session->isOpen(), 400);

        $validated = $request->validate([
            'actual_cash_count' => 'required|numeric|min:0',
        ]);

        try {
            app(TillSessionService::class)->closeTill(
                $session,
                (float) $validated['actual_cash_count']
            );

            $flash = $session->fresh();
            $msg = 'Till closed successfully.';
            if ($flash->variance != 0) {
                $direction = $flash->variance > 0 ? 'overage' : 'shortage';
                $msg .= " Variance: {$direction} of $" . number_format(abs($flash->variance), 2);
            }

            return redirect()->route('pos.register.index')->with('success', $msg);
        } catch (\LogicException $e) {
            return back()->withErrors(['actual_cash_count' => $e->getMessage()])->withInput();
        }
    }

    public function show(PosCashierSession $session)
    {
        $companyId = session('current_company_id');
        abort_unless($session->company_id === $companyId, 403);

        $session->load(['terminal', 'user', 'journalEntry.lines.account']);

        return view('pos.till-sessions.show', compact('session'));
    }
}
