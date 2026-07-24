<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
