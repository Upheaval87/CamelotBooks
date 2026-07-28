<?php

use App\Models\SystemSetting;

if (!function_exists('format_money')) {
    /**
     * Format a numeric amount as currency using the company's localization settings.
     *
     * @param  float|int|string  $amount
     * @param  int|null  $companyId  Falls back to session('current_company_id')
     * @param  int  $decimals  Number of decimal places (default 2)
     * @return string
     */
    function format_money($amount, ?int $companyId = null, int $decimals = 2): string
    {
        $companyId = $companyId ?? session('current_company_id');
        $amount = (float) $amount;

        $symbol = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        $formatted = number_format(abs($amount), $decimals, '.', ',');
        $negative = $amount < 0 ? '-' : '';

        return $negative . $symbol . $formatted;
    }
}

if (!function_exists('format_number')) {
    /**
     * Format a numeric value without currency symbol.
     *
     * @param  float|int|string  $amount
     * @param  int  $decimals  Number of decimal places (default 2)
     * @return string
     */
    function format_number($amount, int $decimals = 2): string
    {
        $amount = (float) $amount;
        $formatted = number_format(abs($amount), $decimals, '.', ',');
        $negative = $amount < 0 ? '-' : '';

        return $negative . $formatted;
    }
}
