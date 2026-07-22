<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $totalAccounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $journalEntriesThisMonth = JournalEntry::where('company_id', $companyId)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->count();

        $pendingApprovals = JournalEntry::where('company_id', $companyId)
            ->where('status', JournalEntry::STATUS_PENDING_APPROVAL)
            ->count();

        return view('dashboard', compact('totalAccounts', 'journalEntriesThisMonth', 'pendingApprovals'));
    }
}
