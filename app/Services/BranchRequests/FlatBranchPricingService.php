<?php

namespace App\Services\BranchRequests;

/**
 * Default pricing: a flat per-branch unit price from config/branch_requests.php
 * with an optional tax rate. All money is computed here and frozen onto the
 * quotation, so the total can never drift after issue.
 */
class FlatBranchPricingService implements BranchPricingService
{
    public function quote(int $quantity, array $context = []): array
    {
        $quantity = max(1, $quantity);

        $unitPrice = round((float) config('branch_requests.unit_price_per_branch'), 2);
        $taxRate = (float) config('branch_requests.tax_rate');

        $subtotal = round($unitPrice * $quantity, 2);
        $taxAmount = round($subtotal * $taxRate, 2);
        $total = round($subtotal + $taxAmount, 2);

        return [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency_code' => $context['currency_code'] ?? 'USD',
            'breakdown' => [
                'per_branch' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ],
        ];
    }
}
