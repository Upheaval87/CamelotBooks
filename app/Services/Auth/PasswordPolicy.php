<?php

namespace App\Services\Auth;

use App\Http\Controllers\Admin\SecuritySettingsController;
use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    /**
     * Build the validation rule for a given company. Pass null when there is
     * no company context (password reset, registration) — the global defaults
     * apply.
     */
    public function ruleFor(?int $companyId = null): Password
    {
        $policy = $this->policy($companyId);

        $rule = Password::min((int) $policy['min_length']);

        $upper = (int) $policy['require_uppercase'] === 1;
        $lower = (int) $policy['require_lowercase'] === 1;

        if ($upper && $lower) {
            $rule->mixedCase();
        } else {
            $custom = [];
            if ($upper) {
                $custom[] = 'regex:/[A-Z]/';
            }
            if ($lower) {
                $custom[] = 'regex:/[a-z]/';
            }
            if ($custom) {
                $rule->rules($custom);
            }
        }

        if ((int) $policy['require_number'] === 1) {
            $rule->numbers();
        }

        if ((int) $policy['require_symbol'] === 1) {
            $rule->symbols();
        }

        return $rule;
    }

    /**
     * Checklist items describing exactly what ruleFor() enforces, so the UI
     * can never drift from the backend policy.
     */
    public function checklist(?int $companyId = null): array
    {
        $policy = $this->policy($companyId);

        $items = [[
            'key' => 'length',
            'min' => (int) $policy['min_length'],
            'label' => __('At least :count characters', ['count' => $policy['min_length']]),
        ]];

        if ((int) $policy['require_uppercase'] === 1) {
            $items[] = ['key' => 'uppercase', 'label' => __('One uppercase letter (A–Z)')];
        }

        if ((int) $policy['require_lowercase'] === 1) {
            $items[] = ['key' => 'lowercase', 'label' => __('One lowercase letter (a–z)')];
        }

        if ((int) $policy['require_number'] === 1) {
            $items[] = ['key' => 'number', 'label' => __('One number (0–9)')];
        }

        if ((int) $policy['require_symbol'] === 1) {
            $items[] = ['key' => 'symbol', 'label' => __('One special character (!@#$%…)')];
        }

        return $items;
    }

    public function policy(?int $companyId = null): array
    {
        return SecuritySettingsController::getPasswordPolicy($companyId);
    }
}
