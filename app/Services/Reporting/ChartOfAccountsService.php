<?php

namespace App\Services\Reporting;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    public function generate(int $companyId, ?string $type = null): array
    {
        $query = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code');

        if ($type) {
            $query->where('type', $type);
        }

        $accounts = $query->get();

        $grouped = $accounts->groupBy('type')->map(function ($typeAccounts, $type) {
            return [
                'type' => $type,
                'accounts' => $typeAccounts->map(fn ($a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'sub_type' => $a->sub_type,
                    'description' => $a->description,
                    'opening_balance' => (float) $a->opening_balance,
                    'current_balance' => (float) $a->current_balance,
                ])->toArray(),
            ];
        })->values()->toArray();

        $types = ['asset', 'liability', 'equity', 'income', 'expense'];

        return [
            'grouped' => $grouped,
            'types' => $types,
            'selected_type' => $type,
            'total_accounts' => $accounts->count(),
        ];
    }
}
