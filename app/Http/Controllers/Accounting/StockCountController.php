<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\StockCount;
use App\Services\Inventory\StockCountService;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $counts = StockCount::where('company_id', $companyId)
            ->with(['branch', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.stock-counts.index', compact('counts'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.stock-counts.create', compact('branches'));
    }

    public function store(Request $request, StockCountService $service)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = $companyId;

        try {
            $count = $service->createCount($validated, $userId);

            return redirect()->route('accounting.stock-counts.edit', $count)
                ->with('success', "Count {$count->count_number} created. Enter physical counts.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(StockCount $count)
    {
        $companyId = session('current_company_id');

        if ($count->company_id !== $companyId) {
            abort(404);
        }

        $count->load('lines.product');

        return view('accounting.stock-counts.edit', compact('count'));
    }

    public function update(Request $request, StockCount $count, StockCountService $service)
    {
        $companyId = session('current_company_id');

        if ($count->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'counts' => 'required|array',
            'counts.*' => 'nullable|numeric|min:0',
        ]);

        try {
            $service->updateCountLines($count, $validated['counts']);

            if ($request->has('post_count')) {
                $service->postCount($count, auth()->id());
                return redirect()->route('accounting.stock-counts.show', $count)
                    ->with('success', "Count {$count->count_number} posted successfully.");
            }

            return redirect()->route('accounting.stock-counts.edit', $count)
                ->with('success', 'Count quantities updated.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(StockCount $count)
    {
        $companyId = session('current_company_id');

        if ($count->company_id !== $companyId) {
            abort(404);
        }

        $count->load('lines.product', 'branch', 'creator', 'journalEntry');

        return view('accounting.stock-counts.show', compact('count'));
    }
}
