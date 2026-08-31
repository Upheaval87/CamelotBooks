<?php

namespace App\Http\Controllers\Accounting\Concerns;

use App\Models\Branch;
use App\Models\Company;
use App\Models\SystemSetting;

/**
 * Shared builder for the branded financial-statement PDF/print wrapper.
 *
 * Resolves the per-company metadata used by the APPENDIX-A sheet chrome in
 * `accounting/print-export.blade.php` (logo lockup, organisation line, title,
 * meta strip) so the five statement controllers stay thin. All values come from
 * settings — nothing is hard-coded.
 */
trait StatementPdfMeta
{
    /**
     * Build the `$meta` array passed to the branded print wrapper.
     *
     * @param  Company  $company
     * @param  int|null  $branchId  Branch filter (or company head office when null)
     * @param  string|null  $preparedBy  Display label, e.g. "Jane Doe · accountant"
     * @param  string  $title  Statement title, e.g. "Statement of Changes in Equity"
     * @param  string|null  $periodLabel  e.g. "For the period 01 Jul 2026 — 31 Jul 2026"
     * @param  string  $basis  Defaults to company setting or 'Accrual'
     * @return array
     */
    protected function statementPdfMeta(
        Company $company,
        ?int $branchId,
        ?string $preparedBy,
        string $title,
        ?string $periodLabel = null,
        string $basis = 'Accrual'
    ): array {
        $companyId = (int) $company->getKey();

        $branch = null;
        if ($branchId) {
            $branch = Branch::where('company_id', $companyId)->find($branchId);
        }
        if (! $branch) {
            $branch = Branch::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN name LIKE '%Head Office%' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->first();
        }

        $symbol  = (string) SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        $decimal = (int) SystemSetting::getValue('currency', 'decimal_places', $companyId, '2');

        return [
            'company'     => $company,
            'branchLine'  => $branch?->name,
            'tpin'        => $company->tax_id,
            'title'       => $title,
            'footerTitle' => $title,
            'periodLabel' => $periodLabel,
            'preparedBy'  => $preparedBy ?? '—',
            'preparedAt'  => now(),
            'basis'       => $basis,
            'currency'    => trim(($company->base_currency ? $company->base_currency.' ' : '').$symbol) ?: $symbol,
            'decimals'    => $decimal,
        ];
    }

    /**
     * Format a label for the current user (name + role) for the meta strip.
     */
    protected function statementPreparedBy(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($name === '') {
            $name = $user->name ?? 'User';
        }
        $role = $user->role_in_current_company;
        return $role ? "{$name} · {$role}" : $name;
    }
}
