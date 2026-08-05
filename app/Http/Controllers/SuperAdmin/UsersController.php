<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->withCount('companyAssignments')
            ->orderBy('name')
            ->get();

        return view('superadmin.users.index', compact('users'));
    }

    public function create()
    {
        return view('superadmin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_super_admin' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_super_admin' => (bool) ($validated['is_super_admin'] ?? false),
            'is_active' => true,
        ]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_USER_CREATED,
            null,
            'user',
            $user->id,
            null,
            ['name' => $user->name, 'email' => $user->email, 'is_super_admin' => $user->is_super_admin],
            "User '{$user->name}' created."
        );

        return redirect()->route('superadmin.users.show', $user)->with('success', 'User created.');
    }

    public function show(User $user)
    {
        $assignments = $user->companyAssignments()->with('company')->orderBy('id')->get();
        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        return view('superadmin.users.show', compact('user', 'assignments', 'companies'));
    }

    public function edit(User $user)
    {
        return view('superadmin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'is_super_admin' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'is_super_admin' => $user->is_super_admin,
            'is_active' => $user->is_active,
        ];

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        $user->is_super_admin = (bool) ($validated['is_super_admin'] ?? false);
        $user->is_active = (bool) ($validated['is_active'] ?? true);
        $user->save();

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_USER_UPDATED,
            null,
            'user',
            $user->id,
            $before,
            [
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'is_active' => $user->is_active,
            ],
            "User '{$user->name}' updated."
        );

        return redirect()->route('superadmin.users.show', $user)->with('success', 'User updated.');
    }

    public function deactivate(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if (! $user->is_active) {
            return back()->with('info', 'User is already deactivated.');
        }

        $user->update(['is_active' => false]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_USER_DEACTIVATED,
            null,
            'user',
            $user->id,
            ['is_active' => true],
            ['is_active' => false],
            "User '{$user->name}' deactivated."
        );

        return redirect()->route('superadmin.users.show', $user)->with('success', 'User deactivated.');
    }

    public function reactivate(Request $request, User $user)
    {
        if ($user->is_active) {
            return back()->with('info', 'User is already active.');
        }

        $user->update(['is_active' => true]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_USER_REACTIVATED,
            null,
            'user',
            $user->id,
            ['is_active' => false],
            ['is_active' => true],
            "User '{$user->name}' reactivated."
        );

        return redirect()->route('superadmin.users.show', $user)->with('success', 'User reactivated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => $validated['new_password'],
            'password_changed_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_USER_PASSWORD_RESET,
            null,
            'user',
            $user->id,
            null,
            null,
            "Password reset for user '{$user->name}'."
        );

        return redirect()->route('superadmin.users.show', $user)->with('success', 'Password reset.');
    }
}
