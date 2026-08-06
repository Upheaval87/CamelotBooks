<?php

namespace App\Services\BranchRequests;

/**
 * Quotation pricing for branch requests. Implementations return a frozen
 * pricing payload (see FlatBranchPricingService::quote) that is stored on the
 * billing_quotation row; totals are ALWAYS server-calculated at quote time.
 */
interface BranchPricingService
{
    /**
     * @param  int  $quantity  number of branches requested
     * @param  array<string, mixed>  $context  e.g. ['currency_code' => 'MWK', 'company_id' => 1]
     * @return array{
     *     unit_price: float,
     *     quantity: int,
     *     subtotal: float,
     *     tax_rate: float,
     *     tax_amount: float,
     *     total: float,
     *     currency_code: string,
     *     breakdown: array<string, mixed>,
     * }
     */
    public function quote(int $quantity, array $context = []): array;
}
