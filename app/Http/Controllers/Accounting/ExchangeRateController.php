<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::find($companyId);

        $rates = ExchangeRate::where('company_id', $companyId)
            ->orderByDesc('effective_date')
            ->orderBy('currency_from')
            ->get();

        $baseCurrency = $company->base_currency;

        return view('accounting.exchange-rates.index', compact('rates', 'baseCurrency'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::find($companyId);

        $validated = $request->validate([
            'currency_from' => 'required|string|size:3',
            'currency_to' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0.00000001',
            'effective_date' => 'required|date',
        ]);

        $validated['company_id'] = $companyId;
        $validated['currency_from'] = strtoupper($validated['currency_from']);
        $validated['currency_to'] = strtoupper($validated['currency_to']);

        if ($validated['currency_from'] === $validated['currency_to']) {
            return back()->withErrors(['currency_to' => 'From and To currencies must be different.'])->withInput();
        }

        ExchangeRate::create($validated);

        return redirect()->route('accounting.exchange-rates.index')->with('success', 'Exchange rate saved successfully.');
    }

    public function destroy(ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();

        return redirect()->route('accounting.exchange-rates.index')->with('success', 'Exchange rate deleted.');
    }

    public function bulkStore(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'csv_data' => 'required|string',
        ]);

        $lines = array_filter(array_map('str_getcsv', explode("\n", $validated['csv_data'])));
        $created = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            if (count($line) < 4) {
                $skipped++;
                continue;
            }

            [$from, $to, $rate, $date] = $line;
            $from = strtoupper(trim($from));
            $to = strtoupper(trim($to));
            $rate = (float) trim($rate);
            $date = trim($date);

            if ($from === $to || $rate <= 0) {
                $skipped++;
                continue;
            }

            ExchangeRate::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'currency_from' => $from,
                    'currency_to' => $to,
                    'effective_date' => $date,
                ],
                ['rate' => $rate]
            );
            $created++;
        }

        return redirect()->route('accounting.exchange-rates.index')
            ->with('success', "{$created} rates imported, {$skipped} skipped.");
    }
}
