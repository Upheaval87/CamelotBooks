<?php

namespace App\Services\BankReconciliation;

use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Models\Reconciliation;
use Illuminate\Support\Collection;

class MatchingEngine
{
    public const CONFIDENCE_EXACT = 100.0;
    public const CONFIDENCE_LIKELY = 85.0;

    /**
     * Generate auto-match suggestions for a reconciliation's unmatched statement
     * lines against the bank account's unmatched book transactions.
     *
     * @return array{exact: Collection, likely: Collection, possible: Collection, bank_only: Collection, book_only: Collection}
     */
    public function suggest(Reconciliation $reconciliation): array
    {
        $statementLines = $this->unmatchedStatementLines($reconciliation);
        $transactions = $this->availableTransactions($reconciliation);

        $exact = new Collection();
        $likely = new Collection();
        $possible = new Collection();
        $matchedLineIds = [];

        foreach ($statementLines as $line) {
            $best = null;
            $bestScore = 0;

            foreach ($transactions as $tx) {
                if (in_array($tx->id, $matchedLineIds, true)) {
                    continue;
                }

                $score = $this->score($line, $tx);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $tx;
                }
            }

            if ($best === null || $bestScore <= 0) {
                continue;
            }

            $confidence = $this->confidence($bestScore);
            $matchedLineIds[] = $best->id;

            $suggestion = (object) [
                'line' => $line,
                'transaction' => $best,
                'confidence' => $confidence,
                'label' => $this->label($confidence),
            ];

            if ($confidence >= self::CONFIDENCE_EXACT) {
                $exact->push($suggestion);
            } elseif ($confidence >= self::CONFIDENCE_LIKELY) {
                $likely->push($suggestion);
            } else {
                $possible->push($suggestion);
            }
        }

        return [
            'exact' => $exact,
            'likely' => $likely,
            'possible' => $possible,
            'bank_only' => $statementLines->filter(fn ($line) => !in_array($line->id, $matchedLineIds, true)),
            'book_only' => $this->bookOnlyTransactions($reconciliation),
        ];
    }

    public function unmatchedStatementLines(Reconciliation $reconciliation): Collection
    {
        return BankStatementLine::query()
            ->where('reconciliation_id', $reconciliation->id)
            ->where(function ($q) {
                $q->whereNull('match_id')
                    ->orWhere('status', BankStatementLine::STATUS_UNMATCHED);
            })
            ->orderBy('transaction_date')
            ->get();
    }

    public function availableTransactions(Reconciliation $reconciliation): Collection
    {
        $periodEnd = $reconciliation->period_end ?? $reconciliation->statement_date;

        return BankTransaction::query()
            ->where('company_id', $reconciliation->company_id)
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('reconciliation_status')
                    ->orWhere('reconciliation_status', BankTransaction::RECON_STATUS_UNMATCHED);
            })
            ->where('is_reconciled', false)
            ->where('date', '<=', $periodEnd)
            ->orderBy('date')
            ->get();
    }

    public function bookOnlyTransactions(Reconciliation $reconciliation): Collection
    {
        $periodEnd = $reconciliation->period_end ?? $reconciliation->statement_date;

        return BankTransaction::query()
            ->where('company_id', $reconciliation->company_id)
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->where(function ($q) {
                $q->whereNull('reconciliation_status')
                    ->orWhere('reconciliation_status', BankTransaction::RECON_STATUS_UNMATCHED);
            })
            ->where('is_reconciled', false)
            ->where('date', '<=', $periodEnd)
            ->orderBy('date')
            ->get();
    }

    protected function score(BankStatementLine $line, BankTransaction $tx): float
    {
        $lineAmount = (float) $line->amount;
        $txAmount = (float) $tx->amount;

        if (abs(abs($lineAmount) - abs($txAmount)) > 0.005) {
            return 0;
        }

        $score = 70.0;

        // Sign alignment: a deposit must match a positive transaction.
        $linePositive = $lineAmount >= 0;
        $txPositive = $txAmount >= 0;
        if ($linePositive === $txPositive) {
            $score += 10;
        } else {
            $score -= 25;
        }

        // Date proximity.
        if ($line->transaction_date && $tx->date) {
            $days = abs($line->transaction_date->diffInDays($tx->date));
            if ($days <= 1) {
                $score += 15;
            } elseif ($days <= 7) {
                $score += 10;
            } elseif ($days <= 14) {
                $score += 5;
            }
        }

        // Reference overlap.
        if ($line->reference && $tx->reference) {
            $a = strtolower($line->reference);
            $b = strtolower($tx->reference);
            if (str_contains($a, $b) || str_contains($b, $a)) {
                $score += 5;
            }
        }

        // Description overlap.
        if ($line->description && $tx->description) {
            $a = strtolower($line->description);
            $b = strtolower($tx->description);
            $shorter = strlen($a) <= strlen($b) ? $a : $b;
            if ($shorter !== '' && (str_contains($a, $b) || str_contains($b, $a))) {
                $score += 5;
            }
        }

        return max(0, min(100, $score));
    }

    protected function confidence(float $score): float
    {
        return round($score, 2);
    }

    protected function label(float $confidence): string
    {
        if ($confidence >= self::CONFIDENCE_EXACT) {
            return 'exact';
        }
        if ($confidence >= self::CONFIDENCE_LIKELY) {
            return 'likely';
        }

        return 'possible';
    }
}
