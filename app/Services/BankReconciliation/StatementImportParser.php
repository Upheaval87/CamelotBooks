<?php

namespace App\Services\BankReconciliation;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use InvalidArgumentException;

class StatementImportParser
{
    /**
     * Parse a bank statement file (CSV or XLSX) into normalized rows.
     *
     * @return array{rows: Collection, filename: string, line_count: int}
     */
    public function parse(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'xlsx', 'xls' => $this->parseAuto($path, $originalName),
            default => throw new InvalidArgumentException('Unsupported file type. Only CSV and XLSX are supported.'),
        };
    }

    /**
     * Parse a bank statement file using an explicit column mapping.
     *
     * @param  array{date: int|null, reference: int|null, description: int|null, debit: int|null, credit: int|null, amount: int|null, balance: int|null}  $map  field => 0-based column index
     * @return array{rows: Collection, filename: string, line_count: int}
     */
    public function parseWithMapping(string $path, string $originalName, array $map, bool $hasHeader = true): array
    {
        if (($map['date'] ?? null) === null) {
            throw new InvalidArgumentException('Map the Transaction Date column to continue.');
        }
        if (($map['debit'] ?? null) === null && ($map['credit'] ?? null) === null && ($map['amount'] ?? null) === null) {
            throw new InvalidArgumentException('Map at least one of Debit, Credit or Amount to continue.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = new Collection();
        $isFirstDataRow = true;

        foreach ($this->readRows($path, $extension) as $line) {
            if (empty(array_filter($line))) {
                continue;
            }

            if ($hasHeader && $isFirstDataRow) {
                $isFirstDataRow = false;
                continue;
            }
            $isFirstDataRow = false;

            $normalized = $this->normalizeRowWithMapping($line, $map);
            if ($normalized !== null) {
                $rows->push($normalized);
            }
        }

        return [
            'rows' => $rows,
            'filename' => $originalName,
            'line_count' => $rows->count(),
        ];
    }

    /**
     * Read a file's raw rows so the UI can show the detected header + sample rows
     * before the user commits to a column mapping.
     *
     * @return array{header: array, samples: array, total_rows: int}
     */
    public function preview(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $header = [];
        $samples = [];
        $totalRows = 0;

        foreach ($this->readRows($path, $extension) as $line) {
            if (empty(array_filter($line))) {
                continue;
            }

            $totalRows++;
            if ($header === []) {
                $header = $line;
                continue;
            }
            if (count($samples) < 5) {
                $samples[] = $line;
            }
        }

        return [
            'header' => $header,
            'samples' => $samples,
            'total_rows' => $totalRows,
        ];
    }

    /**
     * Guess a field => column-index mapping from the detected header row.
     *
     * @return array{date: int|null, reference: int|null, description: int|null, debit: int|null, credit: int|null, amount: int|null, balance: int|null}
     */
    public function suggestMapping(array $header): array
    {
        $map = ['date' => null, 'reference' => null, 'description' => null, 'debit' => null, 'credit' => null, 'amount' => null, 'balance' => null];

        foreach ($header as $index => $cell) {
            $h = mb_strtolower(trim((string) $cell));
            $h = preg_replace('/[^a-z]/', '', $h);

            if ($map['date'] === null && ($h === 'date' || str_contains($h, 'transactiondate') || str_contains($h, 'valuedate') || $h === 'posteddate')) {
                $map['date'] = (int) $index;
            } elseif ($map['credit'] === null && ($h === 'credit' || $h === 'deposit' || $h === 'deposits' || str_contains($h, 'deposit'))) {
                $map['credit'] = (int) $index;
            } elseif ($map['debit'] === null && ($h === 'debit' || $h === 'withdrawal' || $h === 'withdrawals' || str_contains($h, 'withdraw'))) {
                $map['debit'] = (int) $index;
            } elseif ($map['amount'] === null && ($h === 'amount' || $h === 'amt' || str_contains($h, 'amount'))) {
                $map['amount'] = (int) $index;
            } elseif ($map['balance'] === null && ($h === 'balance' || $h === 'bal' || str_contains($h, 'balance') || $h === 'closingbalance')) {
                $map['balance'] = (int) $index;
            } elseif ($map['reference'] === null && ($h === 'reference' || $h === 'ref' || str_contains($h, 'referenceno') || $h === 'trnref')) {
                $map['reference'] = (int) $index;
            } elseif ($map['description'] === null && ($h === 'description' || $h === 'narration' || $h === 'particulars' || $h === 'details' || $h === 'memo' || $h === 'transactiondetails' || $h === 'remarks')) {
                $map['description'] = (int) $index;
            }
        }

        return $map;
    }

    protected function parseAuto(string $path, string $filename): array
    {
        $rows = new Collection();
        $headerSkipped = false;

        foreach ($this->readRows($path, strtolower(pathinfo($filename, PATHINFO_EXTENSION))) as $line) {
            if (empty(array_filter($line))) {
                continue;
            }

            if (!$headerSkipped && $this->looksLikeHeader($line)) {
                $headerSkipped = true;
                continue;
            }
            $headerSkipped = true;

            $normalized = $this->normalizeRow($line);
            if ($normalized !== null) {
                $rows->push($normalized);
            }
        }

        return [
            'rows' => $rows,
            'filename' => $filename,
            'line_count' => $rows->count(),
        ];
    }

    /**
     * Read every row from a CSV (delimiter auto-detected) or spreadsheet file.
     */
    protected function readRows(string $path, string $extension): array
    {
        if (in_array($extension, ['xlsx', 'xls'])) {
            try {
                $sheet = IOFactory::load($path)->getActiveSheet();
            } catch (\Throwable $e) {
                throw new InvalidArgumentException('Unable to parse the spreadsheet: ' . $e->getMessage());
            }

            return array_map(
                fn ($raw) => array_map(fn ($cell) => is_scalar($cell) ? trim((string) $cell) : '', $raw),
                $sheet->toArray()
            );
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read the uploaded file.');
        }

        $delimiter = $this->detectDelimiter($path);
        $rows = [];

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map('trim', $line);
        }

        fclose($handle);

        return $rows;
    }

    protected function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ',';
        }

        $sample = '';
        for ($i = 0; $i < 5 && ($chunk = fgets($handle)) !== false; $i++) {
            $sample .= $chunk;
        }
        fclose($handle);

        $counts = [];
        foreach ([',', ';', "\t", '|'] as $candidate) {
            $counts[$candidate] = substr_count($sample, $candidate);
        }
        arsort($counts);
        $best = array_key_first($counts);

        return ($counts[$best] > 0) ? $best : ',';
    }

    protected function looksLikeHeader(array $line): bool
    {
        $joined = strtolower(implode(' ', $line));

        return str_contains($joined, 'date')
            || str_contains($joined, 'description')
            || str_contains($joined, 'narration')
            || str_contains($joined, 'amount')
            || str_contains($joined, 'balance')
            || str_contains($joined, 'reference');
    }

    protected function normalizeRow(array $line): ?array
    {
        $date = $this->extractDate($line);
        if ($date === null) {
            return null;
        }

        $amount = $this->extractAmount($line);
        $balance = $this->extractBalance($line);
        $description = $this->extractDescription($line);
        $reference = $this->extractReference($line);

        return [
            'transaction_date' => $date,
            'description' => $description,
            'reference' => $reference,
            'amount' => $amount,
            'balance' => $balance,
        ];
    }

    protected function normalizeRowWithMapping(array $line, array $map): ?array
    {
        $dateIndex = $map['date'] ?? null;
        if ($dateIndex === null || !array_key_exists($dateIndex, $line)) {
            return null;
        }

        $date = $this->normalizeDate($line[$dateIndex]);
        if ($date === null) {
            return null;
        }

        $description = '';
        if (($index = $map['description'] ?? null) !== null && array_key_exists($index, $line)) {
            $description = trim($line[$index]);
        }
        if ($description === '') {
            $description = 'Imported transaction';
        } else {
            $description = mb_substr($description, 0, 200);
        }

        $reference = null;
        if (($index = $map['reference'] ?? null) !== null && array_key_exists($index, $line)) {
            $reference = trim($line[$index]);
            if ($reference !== '') {
                $reference = mb_substr($reference, 0, 60);
            } else {
                $reference = null;
            }
        }

        if (($index = $map['amount'] ?? null) !== null && array_key_exists($index, $line)) {
            $amount = $this->parseNumber($line[$index]) ?? 0.0;
        } else {
            $debit = 0.0;
            if (($index = $map['debit'] ?? null) !== null && array_key_exists($index, $line)) {
                $debit = $this->parseNumber($line[$index]) ?? 0.0;
            }
            $credit = 0.0;
            if (($index = $map['credit'] ?? null) !== null && array_key_exists($index, $line)) {
                $credit = $this->parseNumber($line[$index]) ?? 0.0;
            }
            $amount = $credit - $debit;
        }

        $balance = null;
        if (($index = $map['balance'] ?? null) !== null && array_key_exists($index, $line)) {
            $balance = $this->parseNumber($line[$index]);
        }

        return [
            'transaction_date' => $date,
            'description' => $description,
            'reference' => $reference,
            'amount' => round($amount, 2),
            'balance' => $balance,
        ];
    }

    protected function extractDate(array $line): ?string
    {
        foreach ($line as $cell) {
            if ($this->isDate($cell)) {
                $normalized = $this->normalizeDate($cell);
                if ($normalized !== null) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    protected function isDate(string $cell): bool
    {
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $cell)) {
            return true;
        }
        if (preg_match('#^\d{1,2}[/-]\d{1,2}[/-]\d{2,4}$#', $cell)) {
            return true;
        }
        if (preg_match('/^\d{1,2}[ .]\d{1,2}[ .]\d{2,4}$/', $cell)) {
            return true;
        }

        return strtotime($cell) !== false;
    }

    protected function normalizeDate(string $cell): ?string
    {
        $trimmed = trim($cell);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $trimmed, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        foreach (['d/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'd.m.Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat($format, $trimmed);
            if ($date && $date->format($format) === $trimmed) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($trimmed);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    protected function extractAmount(array $line): float
    {
        $candidates = [];

        foreach ($line as $cell) {
            $number = $this->parseNumber($cell);
            if ($number !== null) {
                $candidates[] = $number;
            }
        }

        if (count($candidates) === 0) {
            return 0.0;
        }

        // If a separate balance column exists, the amount is the other numeric value.
        if (count($candidates) >= 2) {
            return (float) $candidates[0];
        }

        return (float) $candidates[0];
    }

    protected function extractBalance(array $line): ?float
    {
        $candidates = [];

        foreach ($line as $index => $cell) {
            if (stripos($cell, 'balance') !== false) {
                continue;
            }
            $number = $this->parseNumber($cell);
            if ($number !== null) {
                $candidates[] = ['index' => $index, 'value' => $number];
            }
        }

        if (count($candidates) >= 2) {
            return (float) $candidates[1]['value'];
        }

        return null;
    }

    protected function extractDescription(array $line): string
    {
        foreach ($line as $cell) {
            $trimmed = trim($cell);
            if ($trimmed === '' || $this->isDate($trimmed) || $this->parseNumber($trimmed) !== null) {
                continue;
            }
            if (stripos($trimmed, 'balance') !== false) {
                continue;
            }

            return mb_substr($trimmed, 0, 200);
        }

        return 'Imported transaction';
    }

    protected function extractReference(array $line): ?string
    {
        foreach ($line as $cell) {
            $trimmed = trim($cell);
            if ($trimmed === '' || $this->isDate($trimmed)) {
                continue;
            }
            if (preg_match('/^[A-Z0-9#\/.\-_]{4,40}$/i', $trimmed)) {
                return mb_substr($trimmed, 0, 60);
            }
        }

        return null;
    }

    protected function parseNumber(string $cell): ?float
    {
        $trimmed = trim($cell);
        if ($trimmed === '') {
            return null;
        }

        if (stripos($trimmed, 'balance') !== false) {
            return null;
        }

        // Strip thousands separators while preserving decimal point.
        $cleaned = preg_replace('/[,\s]/', '', $trimmed);
        $cleaned = preg_replace('/^\((.+)\)$/', '-\1', $cleaned);
        $cleaned = str_replace(['MWK', 'USD', 'EUR', 'K'], '', $cleaned);
        $cleaned = trim($cleaned);

        if (!is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }
}
