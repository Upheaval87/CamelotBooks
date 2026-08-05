<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\Tenancy\CompanyAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Two-step authentication:
     *  - super admins land on the Panel (never silently bound to a tenant);
     *  - non-super-admins with exactly one active assignment are auto-selected
     *    into that company (tenant bound) and forwarded to the dashboard;
     *  - anyone else is sent to the company picker.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return redirect(route('panel.dashboard', absolute: false));
        }

        $company = $this->resolveCompanyForLogin($user);

        if ($company) {
            app(CompanyAccessService::class)->enter($user, $company, \App\Models\CompanyAccessLog::ACTION_LOGIN);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect(route('companies.index', absolute: false));
    }

    /**
     * Auto-select target: the single active assignment (or legacy company_user
     * row). Unprovisioned companies are entered in legacy mode, so provisioning
     * state does not gate auto-selection.
     */
    private function resolveCompanyForLogin(User $user): ?Company
    {
        $assignments = $user->activeCompanyAssignments()->get();

        if ($assignments->count() === 1) {
            return Company::query()->find((int) $assignments->first()->company_id);
        }

        if ($assignments->isNotEmpty()) {
            return null;
        }

        $legacy = $user->companies()->get();

        if ($legacy->count() === 1) {
            return Company::query()->find((int) $legacy->first()->id);
        }

        return null;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
