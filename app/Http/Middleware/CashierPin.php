<?php

namespace App\Http\Middleware;

use App\Models\PosTerminal;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CashierPin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('pos_cashier_id')) {
            return redirect()->route('pos.cashier.login')
                ->with('error', 'Please enter your cashier PIN to continue.');
        }

        if (!session('pos_terminal_id')) {
            return redirect()->route('pos.cashier.login')
                ->with('error', 'Please select a terminal.');
        }

        $terminal = PosTerminal::find(session('pos_terminal_id'));

        if ($terminal && $terminal->cashier_pin_timeout_minutes > 0 && session('pos_session_started_at')) {
            $startedAt = Carbon::parse(session('pos_session_started_at'));

            if ($startedAt->addMinutes($terminal->cashier_pin_timeout_minutes)->isPast()) {
                session()->forget([
                    'pos_cashier_id',
                    'pos_cashier_name',
                    'pos_terminal_id',
                    'pos_terminal_identifier',
                    'pos_terminal_branch_id',
                    'pos_session_started_at',
                ]);

                return redirect()->route('pos.cashier.login')
                    ->with('error', 'Your session has expired. Please enter your PIN again.');
            }
        }

        return $next($request);
    }
}
