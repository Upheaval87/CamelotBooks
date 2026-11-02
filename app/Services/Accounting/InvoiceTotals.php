<?php

namespace App\Services\Accounting;

use Illuminate\Support\Collection;

/**
 * INVOICES MODULE 5.1 — the single place a document's subtotal / tax / grand
 * total are derived from its lines.
 *
 * `InvoiceService` writes these onto the row so the register, collections view,
 * queue, PDF and reports read columns instead of re-summing lines. Both callers
 * must agree exactly, otherwise a posted invoice and its register row would
 * disagree on the money, so the arithmetic lives here once.
 *
 * Rounding matches the per-line rounding already applied by
 * `InvoiceService::computeLineTotals()`: each line's own share of tax is
 * rounded to 2dp before it is summed, which is why tax is accumulated per line
 * rather than computed on the subtotal.
 */
final class InvoiceTotals
{
    /**
     * @param  iterable<InvoiceLine|object>  $lines
     * @return array{subtotal: float, tax_total: float, grand_total: float}
     */
    public static function fromLines(iterable $lines): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($lines as $line) {
            $net = self::amountOf($line, 'amount');
            $tax = self::amountOf($line, 'tax_amount');

            $subtotal += $net;
            $taxTotal += $tax;
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => round($subtotal + $taxTotal, 2),
        ];
    }

    /**
     * Rebalances a single line so the stored `amount` / `tax_amount` /
     * `line_total` triple can never disagree with the quantity and unit price
     * that were entered.
     *
     * NOTE on `discount`: the existing `InvoiceService::computeLineTotals()`
     * treats it as a **flat currency amount off the line**, not a percentage,
     * and the AR module's line card (§4.4) has no discount field at all. This
     * method therefore preserves that established behaviour verbatim — changing
     * it would silently restate every existing invoice total.
     *
     * @param  array{quantity?: mixed, unit_price?: mixed, discount?: mixed, tax_rate?: mixed}  $input
     * @return array{amount: float, tax_amount: float, line_total: float}
     */
    public static function forLine(array $input): array
    {
        $quantity = (float) ($input['quantity'] ?? 1);
        $unitPrice = (float) ($input['unit_price'] ?? 0);
        $discount = (float) ($input['discount'] ?? 0);
        $taxRate = (float) ($input['tax_rate'] ?? 0);

        $amount = ($quantity * $unitPrice) - $discount;
        $taxAmount = $amount * $taxRate / 100;

        return [
            'amount' => round($amount, 2),
            'tax_amount' => round($taxAmount, 2),
            'line_total' => round($amount + $taxAmount, 2),
        ];
    }

    /**
     * `amount` is the net-of-discount figure and `line_total` is net + tax, so a
     * legacy row missing `amount` is reconstructed from `line_total` less tax
     * rather than silently contributing zero to the subtotal.
     */
    private static function amountOf(object $line, string $key): float
    {
        $value = $line->{$key};

        if ($value !== null) {
            return (float) $value;
        }

        if ($key === 'amount') {
            $lineTotal = (float) ($line->line_total ?? 0);

            return round($lineTotal - (float) ($line->tax_amount ?? 0), 2);
        }

        return 0.0;
    }

    /** Convenience wrapper for a loaded line collection. */
    public static function fromCollection(Collection $lines): array
    {
        return self::fromLines($lines);
    }
}