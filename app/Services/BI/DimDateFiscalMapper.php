<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\MartConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DimDateFiscalMapper
{
    use MartConnection;

    public function mapFiscalYears(): int
    {
        $fiscalYears = $this->martTable('fiscal_years')->get();
        $updated = 0;

        foreach ($fiscalYears as $fy) {
            $start = Carbon::parse($fy->start_date);
            $end = Carbon::parse($fy->end_date);
            $year = (int) $fy->label;

            $current = $start->copy()->startOfDay();
            while ($current->lte($end)) {
                $dateKey = (int) $current->format('Ymd');
                $monthInFY = (int) $start->diffInMonths($current) + 1;
                $fiscalQuarter = (int) ceil($monthInFY / 3);

                $this->martTable('dim_date')
                    ->where('date_key', $dateKey)
                    ->update([
                        'fiscal_year_label' => $year,
                        'fiscal_quarter'    => $fiscalQuarter,
                        'fiscal_month'      => $monthInFY,
                        'fiscal_year_id'    => $fy->id,
                    ]);

                $current->addDay();
            }

            $updated++;
        }

        return $updated;
    }
}
