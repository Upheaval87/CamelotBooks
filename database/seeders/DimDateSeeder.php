<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DimDateSeeder extends Seeder
{
    public function run(?string $connection = null): void
    {
        $start = Carbon::create(2022, 1, 1);
        $end = Carbon::create(2027, 12, 31);

        $rows = [];
        while ($start->lte($end)) {
            $dateKey = (int) $start->format('Ymd');
            $year = (int) $start->format('Y');
            $quarter = (int) ceil($start->month / 3);
            $week = (int) $start->weekOfYear;

            $rows[] = [
                'date_key'           => $dateKey,
                'full_date'          => $start->toDateString(),
                'year'               => $year,
                'quarter'            => $quarter,
                'month'              => (int) $start->format('m'),
                'week'               => $week,
                'day_of_month'       => (int) $start->format('d'),
                'day_of_week'        => (int) $start->dayOfWeek,
                'month_name'         => $start->monthName,
                'quarter_name'       => "Q{$quarter} {$year}",
                'is_weekend'         => $start->isWeekend(),
                'fiscal_year_label'  => null,
                'fiscal_quarter'     => null,
                'fiscal_month'       => null,
                'fiscal_year_id'     => null,
            ];

            $start->addDay();
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::connection($connection)->table('dim_date')->insert($chunk);
        }
    }
}
