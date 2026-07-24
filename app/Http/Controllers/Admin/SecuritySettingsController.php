<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SecuritySettingsController extends Controller
{
    private const GROUP = 'security';

    private const PASSWORD_RULES = [
        'min_length' => ['default' => '8', 'label' => 'Minimum Password Length', 'type' => 'number'],
        'require_uppercase' => ['default' => '1', 'label' => 'Require Uppercase Letters', 'type' => 'boolean'],
        'require_lowercase' => ['default' => '1', 'label' => 'Require Lowercase Letters', 'type' => 'boolean'],
        'require_number' => ['default' => '1', 'label' => 'Require Numbers', 'type' => 'boolean'],
        'require_symbol' => ['default' => '0', 'label' => 'Require Special Characters', 'type' => 'boolean'],
        'expiry_days' => ['default' => '0', 'label' => 'Password Expiry (days, 0=never)', 'type' => 'number'],
        'history_count' => ['default' => '0', 'label' => 'Password History (prevent reuse of last N, 0=disabled)', 'type' => 'number'],
    ];

    private const SESSION_RULES = [
        'timeout_minutes' => ['default' => '120', 'label' => 'Session Timeout (minutes)', 'type' => 'number'],
    ];

    private const LOGIN_RULES = [
        'max_attempts' => ['default' => '5', 'label' => 'Max Failed Login Attempts', 'type' => 'number'],
        'lockout_minutes' => ['default' => '15', 'label' => 'Lockout Duration (minutes)', 'type' => 'number'],
    ];

    private const TFA_RULES = [
        'require_for_admins' => ['default' => '0', 'label' => 'Require 2FA for Admin Roles', 'type' => 'boolean'],
    ];

    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $settings = SystemSetting::getMany(self::GROUP, $companyId);

        return view('admin.security.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $validated = $request->validate([
            'password.min_length' => 'required|integer|min:4|max:128',
            'password.require_uppercase' => 'boolean',
            'password.require_lowercase' => 'boolean',
            'password.require_number' => 'boolean',
            'password.require_symbol' => 'boolean',
            'password.expiry_days' => 'required|integer|min:0|max:365',
            'password.history_count' => 'required|integer|min:0|max:24',
            'session.timeout_minutes' => 'required|integer|min:5|max:480',
            'login.max_attempts' => 'required|integer|min:1|max:20',
            'login.lockout_minutes' => 'required|integer|min:1|max:1440',
            'tfa.require_for_admins' => 'boolean',
        ]);

        foreach ($validated as $group => $settings) {
            foreach ($settings as $key => $value) {
                SystemSetting::setValue(self::GROUP, "{$group}.{$key}", $value, $companyId);
            }
        }

        return redirect()->route('admin.security.index')->with('success', 'Security settings updated successfully.');
    }

    public static function getPasswordPolicy(?int $companyId = null): array
    {
        $rules = self::PASSWORD_RULES;
        $policy = [];

        foreach ($rules as $key => $config) {
            $policy[$key] = SystemSetting::getValue(
                self::GROUP,
                "password.{$key}",
                $companyId,
                $config['default']
            );
        }

        return $policy;
    }

    public static function get2FARequirement(?int $companyId = null): bool
    {
        return (bool) SystemSetting::getValue(
            self::GROUP,
            'tfa.require_for_admins',
            $companyId,
            false
        );
    }
}
