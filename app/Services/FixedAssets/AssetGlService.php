<?php

namespace App\Services\FixedAssets;

use App\Models\Account;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntry;
use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaDepRun;
use App\Models\FixedAssets\FaDisposal;
use App\Models\FixedAssets\FaImpairment;
use App\Models\FixedAssets\FaRevaluation;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\Log;

class AssetGlService
{
    public function __construct(
        private JournalPostingEngine $jpe,
    ) {
    }

    /**
     * Post activation JE:
     *   DR  Fixed Asset (asset_account_id) = acquisition_cost
     *   CR  Accounts Payable / clearing    = acquisition_cost
     */
    public function postActivation(FaAsset $asset, int $userId): ?JournalEntry
    {
        $creditAccount = $this->resolveActivationCreditAccount($asset);

        if (!$creditAccount) {
            Log::warning("FA activation skipped GL posting — no AP/clearing account for asset {$asset->asset_code}");
            return null;
        }

        $je = $this->jpe->post([
            'company_id'    => $asset->company_id,
            'created_by'    => $userId,
            'date'          => $asset->in_service_date?->format('Y-m-d') ?? now()->toDateString(),
            'source_module' => 'fixed_assets',
            'reference'     => $asset->asset_code,
            'memo'          => "Activation of asset {$asset->asset_code} — {$asset->name}",
            'lines'         => [
                [
                    'account_id'  => $asset->asset_account_id,
                    'debit'       => (float) $asset->acquisition_cost,
                    'credit'      => 0,
                    'entity_type' => FaAsset::class,
                    'entity_id'   => $asset->id,
                    'memo'        => "Asset {$asset->asset_code}",
                ],
                [
                    'account_id'  => $creditAccount->id,
                    'debit'       => 0,
                    'credit'      => (float) $asset->acquisition_cost,
                    'entity_type' => FaAsset::class,
                    'entity_id'   => $asset->id,
                    'memo'        => "Asset {$asset->asset_code} acquisition",
                ],
            ],
        ]);

        return $je;
    }

    /**
     * Post depreciation run JE (one JE for the entire run):
     *   DR  Depreciation Expense (dep_expense_account_id) = amount per line
     *   CR  Accumulated Depreciation (accum_dep_account_id) = amount per line
     */
    public function postDepreciationRun(FaDepRun $run, int $userId): ?JournalEntry
    {
        $lines = $run->lines()
            ->where('status', 'posted')
            ->where('depreciation_amount', '>', 0)
            ->get();

        if ($lines->isEmpty()) {
            return null;
        }

        $totalAmount = (float) $run->total_depreciation;
        if ($totalAmount <= 0) {
            return null;
        }

        $jeLines = [];
        $grouped = [];

        foreach ($lines as $line) {
            $asset = $line->asset;
            $key = "{$asset->dep_expense_account_id}_{$asset->accum_dep_account_id}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'expense_account' => $asset->dep_expense_account_id,
                    'accum_account'   => $asset->accum_dep_account_id,
                    'amount'          => 0,
                    'asset_ids'       => [],
                ];
            }
            $grouped[$key]['amount'] += (float) $line->depreciation_amount;
            $grouped[$key]['asset_ids'][] = $asset->id;
        }

        foreach ($grouped as $group) {
            $jeLines[] = [
                'account_id' => $group['expense_account'],
                'debit'      => round($group['amount'], 2),
                'credit'     => 0,
                'memo'       => "Depreciation — {$run->period}",
            ];
            $jeLines[] = [
                'account_id' => $group['accum_account'],
                'debit'      => 0,
                'credit'     => round($group['amount'], 2),
                'memo'       => "Depreciation — {$run->period}",
            ];
        }

        $je = $this->jpe->post([
            'company_id'    => $run->company_id,
            'created_by'    => $userId,
            'date'          => $run->period_end->format('Y-m-d'),
            'source_module' => 'fixed_assets',
            'reference'     => $run->run_number,
            'memo'          => "Depreciation run {$run->run_number} — {$run->period}",
            'lines'         => $jeLines,
        ]);

        return $je;
    }

    /**
     * Reverse depreciation run JE:
     *   DR  Accumulated Depreciation (accum_dep_account_id) = amount per line
     *   CR  Depreciation Expense (dep_expense_account_id) = amount per line
     */
    public function reverseDepreciationRun(FaDepRun $run, int $userId): ?JournalEntry
    {
        $lines = $run->lines()
            ->where('status', 'posted')
            ->where('depreciation_amount', '>', 0)
            ->get();

        if ($lines->isEmpty()) {
            return null;
        }

        $totalAmount = (float) $run->total_depreciation;
        if ($totalAmount <= 0) {
            return null;
        }

        $jeLines = [];
        $grouped = [];

        foreach ($lines as $line) {
            $asset = $line->asset;
            $key = "{$asset->accum_dep_account_id}_{$asset->dep_expense_account_id}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'accum_account'   => $asset->accum_dep_account_id,
                    'expense_account' => $asset->dep_expense_account_id,
                    'amount'          => 0,
                ];
            }
            $grouped[$key]['amount'] += (float) $line->depreciation_amount;
        }

        foreach ($grouped as $group) {
            $jeLines[] = [
                'account_id' => $group['accum_account'],
                'debit'      => round($group['amount'], 2),
                'credit'     => 0,
                'memo'       => "Reversal of depreciation — {$run->period}",
            ];
            $jeLines[] = [
                'account_id' => $group['expense_account'],
                'debit'      => 0,
                'credit'     => round($group['amount'], 2),
                'memo'       => "Reversal of depreciation — {$run->period}",
            ];
        }

        $je = $this->jpe->post([
            'company_id'    => $run->company_id,
            'created_by'    => $userId,
            'date'          => now()->toDateString(),
            'source_module' => 'fixed_assets',
            'reference'     => $run->run_number,
            'memo'          => "Reversal of depreciation run {$run->run_number} — {$run->period}",
            'lines'         => $jeLines,
        ]);

        return $je;
    }

    /**
     * Disposal JE — derecognise asset:
     *   DR  Accumulated Depreciation (accum_dep_account_id) = accumDep + accumImp
     *   CR  Fixed Asset (asset_account_id)                  = acquisition_cost
     *   DR/CR  Gain/Loss on Disposal (disposal_account_id)  = gain_loss
     */
    public function postDisposal(FaDisposal $disposal, int $userId): ?JournalEntry
    {
        $asset = $disposal->asset;

        if (!$asset->asset_account_id || !$asset->accum_dep_account_id || !$asset->disposal_account_id) {
            Log::warning("FA disposal skipped GL posting — missing GL accounts on asset {$asset->asset_code}");
            return null;
        }

        $totalContra = (float) $disposal->accum_depreciation + (float) $disposal->accum_impairment;
        $cost        = (float) $disposal->cost_acquisition;
        $netProceeds = (float) $disposal->net_proceeds;
        $gainLoss    = (float) $disposal->gain_loss;

        // Find a bank/cash account for the proceeds DR
        $bankAccount = DefaultAccountMapping::getAccount($disposal->company_id, 'default_bank');
        if (!$bankAccount) {
            Log::warning("FA disposal skipped GL posting — no default bank account for company {$disposal->company_id}");
            return null;
        }

        $jeLines = [];

        // DR accumulated depreciation (total contra: dep + impairment)
        if ($totalContra > 0) {
            $jeLines[] = [
                'account_id' => $asset->accum_dep_account_id,
                'debit'      => round($totalContra, 2),
                'credit'     => 0,
                'memo'       => "Remove accumulated depreciation/impairment for {$asset->asset_code}",
            ];
        }

        // DR bank/cash for net proceeds received
        if ($netProceeds > 0) {
            $jeLines[] = [
                'account_id' => $bankAccount->id,
                'debit'      => round($netProceeds, 2),
                'credit'     => 0,
                'memo'       => "Net proceeds from disposal of {$asset->asset_code}",
            ];
        }

        // CR the fixed asset
        $jeLines[] = [
            'account_id' => $asset->asset_account_id,
            'debit'      => 0,
            'credit'     => round($cost, 2),
            'memo'       => "Derecognise asset {$asset->asset_code}",
        ];

        // DR or CR the gain/loss account
        if ($gainLoss > 0) {
            // Gain: CR the disposal account
            $jeLines[] = [
                'account_id' => $asset->disposal_account_id,
                'debit'      => 0,
                'credit'     => round($gainLoss, 2),
                'memo'       => "Gain on disposal of {$asset->asset_code}",
            ];
        } elseif ($gainLoss < 0) {
            // Loss: DR the disposal account
            $jeLines[] = [
                'account_id' => $asset->disposal_account_id,
                'debit'      => round(abs($gainLoss), 2),
                'credit'     => 0,
                'memo'       => "Loss on disposal of {$asset->asset_code}",
            ];
        }

        $je = $this->jpe->post([
            'company_id'    => $disposal->company_id,
            'created_by'    => $userId,
            'date'          => $disposal->disposal_date?->format('Y-m-d') ?? now()->toDateString(),
            'source_module' => 'fixed_assets',
            'reference'     => $disposal->id,
            'memo'          => "Disposal of asset {$asset->asset_code}",
            'lines'         => $jeLines,
        ]);

        return $je;
    }

    /**
     * Impairment JE:
     *   DR  Impairment Loss (disposal_account_id)       = impairment_loss
     *   CR  Accumulated Depreciation (accum_dep_account_id) = impairment_loss
     */
    public function postImpairment(FaImpairment $impairment, int $userId): ?JournalEntry
    {
        $asset  = $impairment->asset;
        $loss   = (float) $impairment->impairment_loss;

        if ($loss <= 0) {
            return null;
        }

        if (!$asset->accum_dep_account_id) {
            Log::warning("FA impairment skipped GL posting — missing accum_dep_account_id on asset {$asset->asset_code}");
            return null;
        }

        $impairmentAccount = $this->resolveImpairmentLossAccount($asset->company_id);

        if (!$impairmentAccount) {
            Log::warning("FA impairment skipped GL posting — no impairment loss account for company {$asset->company_id}");
            return null;
        }

        $je = $this->jpe->post([
            'company_id'    => $impairment->company_id,
            'created_by'    => $userId,
            'date'          => $impairment->impairment_date?->format('Y-m-d') ?? now()->toDateString(),
            'source_module' => 'fixed_assets',
            'reference'     => $impairment->id,
            'memo'          => "Impairment of asset {$asset->asset_code} — loss: {$loss}",
            'lines'         => [
                [
                    'account_id' => $impairmentAccount->id,
                    'debit'      => round($loss, 2),
                    'credit'     => 0,
                    'memo'       => "Impairment loss — {$asset->asset_code}",
                ],
                [
                    'account_id' => $asset->accum_dep_account_id,
                    'debit'      => 0,
                    'credit'     => round($loss, 2),
                    'memo'       => "Impairment charge — {$asset->asset_code}",
                ],
            ],
        ]);

        return $je;
    }

    /**
     * Revaluation JE:
     *   Upward:  DR Fixed Asset (asset_account_id)      = surplus
     *            CR Revaluation Surplus (3300-equivalent) = surplus
     *   Downward: DR Revaluation Surplus                 = |surplus|
     *            CR Fixed Asset                           = |surplus|
     */
    public function postRevaluation(FaRevaluation $revaluation, int $userId): ?JournalEntry
    {
        $asset   = $revaluation->asset;
        $surplus = (float) $revaluation->surplus_amount;

        if ($surplus == 0) {
            return null;
        }

        if (!$asset->asset_account_id) {
            Log::warning("FA revaluation skipped GL posting — missing asset_account_id on asset {$asset->asset_code}");
            return null;
        }

        $surplusAccount = $this->resolveRevaluationSurplusAccount($asset->company_id);

        if (!$surplusAccount) {
            Log::warning("FA revaluation skipped GL posting — no revaluation surplus account for company {$asset->company_id}");
            return null;
        }

        $absSurplus = abs($surplus);
        $isUpward   = $surplus > 0;

        $jeLines = [
            [
                'account_id' => $isUpward ? $asset->asset_account_id : $surplusAccount->id,
                'debit'      => round($absSurplus, 2),
                'credit'     => 0,
                'memo'       => $isUpward
                    ? "Revaluation increase — {$asset->asset_code}"
                    : "Revaluation decrease — {$asset->asset_code}",
            ],
            [
                'account_id' => $isUpward ? $surplusAccount->id : $asset->asset_account_id,
                'debit'      => 0,
                'credit'     => round($absSurplus, 2),
                'memo'       => $isUpward
                    ? "Revaluation surplus — {$asset->asset_code}"
                    : "Revaluation decrease — {$asset->asset_code}",
            ],
        ];

        $je = $this->jpe->post([
            'company_id'    => $revaluation->company_id,
            'created_by'    => $userId,
            'date'          => $revaluation->revaluation_date?->format('Y-m-d') ?? now()->toDateString(),
            'source_module' => 'fixed_assets',
            'reference'     => $revaluation->id,
            'memo'          => "Revaluation of asset {$asset->asset_code} — surplus: {$surplus}",
            'lines'         => $jeLines,
        ]);

        return $je;
    }

    // ── Account resolution helpers ───────────────

    private function resolveActivationCreditAccount(FaAsset $asset): ?Account
    {
        $apAccount = \App\Models\DefaultAccountMapping::getAccount($asset->company_id, 'accounts_payable');

        if ($apAccount) {
            return $apAccount;
        }

        return Account::where('company_id', $asset->company_id)
            ->where('code', '2000')
            ->where('is_active', true)
            ->first();
    }

    private function resolveImpairmentLossAccount(int $companyId): ?Account
    {
        $impairmentAccount = \App\Models\DefaultAccountMapping::getAccount($companyId, 'impairment_loss');

        if ($impairmentAccount) {
            return $impairmentAccount;
        }

        return Account::where('company_id', $companyId)
            ->where('code', '6500')
            ->where('is_active', true)
            ->first();
    }

    private function resolveRevaluationSurplusAccount(int $companyId): ?Account
    {
        $surplusAccount = \App\Models\DefaultAccountMapping::getAccount($companyId, 'revaluation_surplus');

        if ($surplusAccount) {
            return $surplusAccount;
        }

        return Account::where('company_id', $companyId)
            ->where('code', '3300')
            ->where('is_active', true)
            ->first();
    }
}
