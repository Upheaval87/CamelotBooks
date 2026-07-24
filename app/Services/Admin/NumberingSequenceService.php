<?php

namespace App\Services\Admin;

use App\Models\NumberingSequence;
use Illuminate\Support\Facades\DB;

class NumberingSequenceService
{
    public function getNextNumber(int $companyId, string $documentType): string
    {
        $sequence = DB::transaction(function () use ($companyId, $documentType) {
            $seq = NumberingSequence::where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                return null;
            }

            if ($seq->reset_policy === 'monthly') {
                $currentMonth = now()->format('Ym');
                $lastResetMonth = $seq->updated_at ? $seq->updated_at->format('Ym') : null;
                if ($lastResetMonth !== $currentMonth) {
                    $seq->update(['next_number' => 1]);
                }
            } elseif ($seq->reset_policy === 'annually') {
                $currentYear = now()->format('Y');
                $lastResetYear = $seq->updated_at ? $seq->updated_at->format('Y') : null;
                if ($lastResetYear !== $currentYear) {
                    $seq->update(['next_number' => 1]);
                }
            }

            $number = $seq->next_number;
            $seq->increment('next_number');

            return $seq;
        });

        if (!$sequence) {
            throw new \RuntimeException("No active numbering sequence found for document type: {$documentType}");
        }

        $year = now()->format('Y');
        $month = now()->format('m');
        $padded = str_pad($sequence->next_number - 1, $sequence->padding_width, '0', STR_PAD_LEFT);

        if ($sequence->document_type === 'payroll_run') {
            return "{$sequence->prefix}{$year}{$month}-{$padded}";
        }

        return "{$sequence->prefix}{$year}-{$padded}";
    }

    public function peekNextNumber(int $companyId, string $documentType): ?string
    {
        $sequence = NumberingSequence::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if (!$sequence) {
            return null;
        }

        $year = now()->format('Y');
        $month = now()->format('m');
        $padded = str_pad($sequence->next_number, $sequence->padding_width, '0', STR_PAD_LEFT);

        if ($sequence->document_type === 'payroll_run') {
            return "{$sequence->prefix}{$year}{$month}-{$padded}";
        }

        return "{$sequence->prefix}{$year}-{$padded}";
    }

    public function seedDefaults(int $companyId): void
    {
        $defaults = NumberingSequence::defaultSequences();

        foreach ($defaults as $default) {
            NumberingSequence::create(array_merge($default, ['company_id' => $companyId]));
        }
    }

    public function resetSequence(NumberingSequence $sequence): void
    {
        $sequence->update(['next_number' => 1]);
    }
}
