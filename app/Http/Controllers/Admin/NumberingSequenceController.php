<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NumberingSequence;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Http\Request;

class NumberingSequenceController extends Controller
{
    public function __construct(
        private NumberingSequenceService $service
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $sequences = NumberingSequence::where('company_id', $companyId)
            ->orderBy('document_type')
            ->get();

        $labels = NumberingSequence::documentTypeLabels();

        return view('admin.numbering-sequences.index', compact('sequences', 'labels'));
    }

    public function create(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $labels = NumberingSequence::documentTypeLabels();

        $existingTypes = NumberingSequence::where('company_id', $companyId)
            ->pluck('document_type')
            ->toArray();

        $availableTypes = array_diff_key($labels, array_flip($existingTypes));

        return view('admin.numbering-sequences.create', compact('labels', 'availableTypes'));
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $validated = $request->validate([
            'document_type' => 'required|string|in:' . implode(',', array_keys(NumberingSequence::documentTypeLabels())),
            'prefix' => 'required|string|max:20',
            'padding_width' => 'required|integer|min:1|max:10',
            'reset_policy' => 'required|in:never,annually,monthly',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['next_number'] = 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        NumberingSequence::create($validated);

        return redirect()->route('admin.numbering-sequences.index')
            ->with('success', 'Numbering sequence created successfully.');
    }

    public function show(Request $request, NumberingSequence $numberingSequence)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($numberingSequence->company_id === $companyId, 403);
        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $labels = NumberingSequence::documentTypeLabels();
        $nextPreview = $this->service->peekNextNumber($companyId, $numberingSequence->document_type);

        return view('admin.numbering-sequences.show', compact('numberingSequence', 'labels', 'nextPreview'));
    }

    public function edit(Request $request, NumberingSequence $numberingSequence)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($numberingSequence->company_id === $companyId, 403);
        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $labels = NumberingSequence::documentTypeLabels();

        return view('admin.numbering-sequences.edit', compact('numberingSequence', 'labels'));
    }

    public function update(Request $request, NumberingSequence $numberingSequence)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($numberingSequence->company_id === $companyId, 403);
        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $validated = $request->validate([
            'prefix' => 'required|string|max:20',
            'padding_width' => 'required|integer|min:1|max:10',
            'next_number' => 'required|integer|min:1',
            'reset_policy' => 'required|in:never,annually,monthly',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        $numberingSequence->update($validated);

        return redirect()->route('admin.numbering-sequences.index')
            ->with('success', 'Numbering sequence updated successfully.');
    }

    public function reset(Request $request, NumberingSequence $numberingSequence)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($numberingSequence->company_id === $companyId, 403);
        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $this->service->resetSequence($numberingSequence);

        return redirect()->route('admin.numbering-sequences.show', $numberingSequence)
            ->with('success', 'Sequence reset to 1. Next number will be the first in the new sequence.');
    }
}
