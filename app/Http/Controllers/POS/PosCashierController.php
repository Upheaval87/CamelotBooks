<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        $cashiers = User::whereHas('companies', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('pos_cashier_pin')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'has_pin' => $u->hasPosPin()]);

        return view('pos.auth.login', compact('terminals', 'cashiers'));
    }

    public function login(Request $request)
    {
        $companyId = session('current_company_id');

        $authType = $request->input('auth_type', 'pin');

        $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
        ]);

        $terminal = PosTerminal::where('id', $request->terminal_id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$terminal) {
            return back()->withErrors(['terminal_id' => 'Terminal not found or inactive.'])->withInput();
        }

        if ($authType === 'password') {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)
                ->where('is_active', true)
                ->whereHas('companies', fn ($q) => $q->where('company_id', $companyId))
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
            }
        } else {
            $request->validate([
                'pin' => 'required|string|min:4|max:10',
            ]);

            $user = User::whereNotNull('pos_cashier_pin')
                ->whereHas('companies', fn ($q) => $q->where('company_id', $companyId))
                ->get()
                ->first(fn ($u) => $u->verifyPosPin($request->pin));

            if (!$user) {
                return back()->withErrors(['pin' => 'Invalid PIN.'])->withInput();
            }
        }

        session([
            'pos_cashier_id' => $user->id,
            'pos_cashier_name' => $user->name,
            'pos_terminal_id' => $terminal->id,
            'pos_terminal_identifier' => $terminal->identifier,
            'pos_terminal_branch_id' => $terminal->branch_id,
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
            'pos_terminal_branch_id',
            'pos_session_started_at',
        ]);

        return redirect()->route('pos.login');
    }
}
