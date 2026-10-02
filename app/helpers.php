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

if (!function_exists('amount_to_words')) {
    /**
     * Render a numeric amount as an English "amount in words" line for printed
     * vouchers (journal voucher totals, debit/credit notes).
     *
     * Pure presentation helper — it performs no rounding of its own beyond the
     * two-decimal split and never touches ledger, posting or permission logic.
     *
     * @param  float|int|string  $amount
     * @param  string|null  $currencyName  Currency name, e.g. "Malawi Kwacha".
     *                                     Omitted entirely when null/blank.
     * @param  int  $decimals  Decimal places to read as minor units (default 2)
     */
    function amount_to_words($amount, ?string $currencyName = null, int $decimals = 2): string
    {
        $amount = round((float) $amount, $decimals);
        $negative = $amount < 0;
        $amount = abs($amount);

        $unitsMinor = (int) round($amount * (10 ** $decimals));
        $major = intdiv($unitsMinor, 10 ** $decimals);
        $minor = $unitsMinor % (10 ** $decimals);

        $words = trim(_number_to_words_major($major));
        $majorIsZero = ($words === '');

        if ($minor > 0) {
            $minorWords = trim(_number_to_words_major($minor));
            if ($minorWords !== '') {
                $words .= ($majorIsZero ? '' : ' and ')
                    . $minorWords . ' ' . ($minor === 1 ? _minor_unit_label() : _minor_unit_label() . 's');
            }
        }

        if ($words === '') {
            $words = 'Zero';
        }

        $prefix = trim((string) $currencyName);
        $out = ($prefix !== '' ? $prefix . ' ' : '')
            . ($negative ? 'negative ' : '')
            . $words . ' only';

        return ucfirst($out);
    }
}

if (!function_exists('_number_to_words_major')) {
    /**
     * Convert a non-negative integer below one billion to English words.
     *
     * @internal used by amount_to_words()
     */
    function _number_to_words_major(int $number): string
    {
        if ($number < 0) {
            return '';
        }

        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen',
        ];
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];
        $scales = [
            1_000_000_000 => 'Billion', 1_000_000 => 'Million', 1_000 => 'Thousand',
        ];

        if ($number < 20) {
            return $ones[$number];
        }
        if ($number < 100) {
            $rest = $number % 10;
            return $tens[intdiv($number, 10)] . ($rest ? '-' . $ones[$rest] : '');
        }
        if ($number < 1000) {
            $rest = $number % 100;
            return $ones[intdiv($number, 100)] . ' Hundred' . ($rest ? ' ' . _number_to_words_major($rest) : '');
        }

        foreach ($scales as $scale => $label) {
            if ($number >= $scale) {
                $rest = $number % $scale;
                return _number_to_words_major(intdiv($number, $scale)) . ' ' . $label
                    . ($rest ? ' ' . _number_to_words_major($rest) : '');
            }
        }

        return '';
    }
}

if (!function_exists('_minor_unit_label')) {
    /**
     * Name for the fractional part, chosen by magnitude.
     *
     * @internal used by amount_to_words()
     */
    function _minor_unit_label(): string
    {
        return 'Cent';
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Format a byte count as a human-readable size string.
     *
     * @param  int|float|null  $bytes
     */
    function format_bytes($bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), $i > 0 ? 1 : 0) . ' ' . $units[$i];
    }
}
