<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use App\Models\User;
use Illuminate\Http\Request;

class PosCashierController extends Controller
{
    public function showLoginForm()
    {
        $companyId = session('current_company_id');
        $terminals = PosTerminal::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('branch')
            ->orderBy('identifier')
            ->get();

        return view('pos.cashier.login', compact('terminals'));
    }

    public function login(Request $request)
    {
        $companyId = session('current_company_id');

        $request->validate([
            'pin' => 'required|string|min:4|max:10',
            'terminal_id' => 'required|exists:pos_terminals,id',
        ]);

        $terminal = PosTerminal::where('id', $request->terminal_id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$terminal) {
            return back()->withErrors(['terminal_id' => 'Terminal not found or inactive.'])->withInput();
        }

        $user = User::where('pos_cashier_pin', $request->pin)
            ->whereNotNull('pos_cashier_pin')
            ->first();

        if (!$user) {
            return back()->withErrors(['pin' => 'Invalid PIN.'])->withInput();
        }

        $hasAccess = $user->companies()->where('company_id', $companyId)->exists();
        if (!$hasAccess) {
            return back()->withErrors(['pin' => 'You do not have access to this company.'])->withInput();
        }

        session([
            'pos_cashier_id' => $user->id,
            'pos_cashier_name' => $user->name,
            'pos_terminal_id' => $terminal->id,
            'pos_terminal_identifier' => $terminal->identifier,
            'pos_session_started_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('pos.dashboard');
    }

    public function logout()
    {
        session()->forget([
            'pos_cashier_id',
            'pos_cashier_name',
            'pos_terminal_id',
            'pos_terminal_identifier',
            'pos_session_started_at',
        ]);

        return redirect()->route('pos.cashier.login');
    }
}
