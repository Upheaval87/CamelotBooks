<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;

class BiRefreshService
{
    public function getLastRefresh(): ?object
    {
        return DB::table('bi_refresh_log')
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->first();
    }

    public function getLastRefreshAgeHuman(): ?string
    {
        $last = $this->getLastRefresh();

        if (!$last) {
            return null;
        }

        return \Carbon\Carbon::parse($last->completed_at)->diffForHumans();
    }

    public function isStale(int $maxMinutes = 1440): bool
    {
        $last = $this->getLastRefresh();

        if (!$last) {
            return true;
        }

        return \Carbon\Carbon::parse($last->completed_at)->diffInMinutes(now()) > $maxMinutes;
    }
}
