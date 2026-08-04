<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\TodoTask;
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

        // Personal task summary (company + current user only).
        $myTasks = TodoTask::query()
            ->forCompany((int) $companyId)
            ->forUser(auth()->id())
            ->active()
            ->get(['id', 'title', 'deadline_date', 'deadline_granularity']);

        $todoOverdue = 0;
        $todoToday = 0;

        foreach ($myTasks as $task) {
            $bucket = TodoTask::bucketKey($task->deadline_date, $task->deadline_granularity);

            if ($bucket === TodoTask::BUCKET_OVERDUE) {
                $todoOverdue++;
            } elseif ($bucket === TodoTask::BUCKET_TODAY) {
                $todoToday++;
            }
        }

        return view('dashboard', compact(
            'totalAccounts',
            'journalEntriesThisMonth',
            'pendingApprovals',
            'myTasks',
            'todoOverdue',
            'todoToday',
        ));
    }
}
