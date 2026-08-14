<?php

namespace App\Http\Controllers;

use App\Models\TodoTask;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TodoTaskController extends Controller
{
    /**
     * A task is always scoped to the company *and* the current user. No
     * role, including admin/system_admin, ever sees another user's tasks.
     */
    private function authorizeAccess(TodoTask $task): void
    {
        if ($task->company_id !== session('current_company_id') || $task->user_id !== auth()->id()) {
            abort(404);
        }
    }

    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return TodoTask::query()
            ->forCompany((int) session('current_company_id'))
            ->forUser(auth()->id())
            ->with('linkable');
    }

    public function index()
    {
        $data = $this->gatherData();

        return view('todo.index', $data);
    }

    /**
     * Server-rendered list fragment used by the topbar "My Tasks" modal.
     * Carries the same buckets/tabs as the full page but no layout chrome,
     * so it can be fetched with fetch() and injected into the modal.
     */
    public function modal()
    {
        $data = $this->gatherData();

        return view('todo.partials.modal-list', $data);
    }

    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate($this->rules());
        $validated['company_id'] = $companyId;
        $validated['user_id'] = auth()->id();
        $validated['status'] = TodoTask::STATUS_ACTIVE;
        $validated = $this->normalizeLink($validated);

        TodoTask::create($validated);

        if ($request->wantsJson()) {
            return $this->jsonOk(__('Task added.'));
        }

        return redirect()->route('todo.index')
            ->with('success', __('Task added.'));
    }

    public function update(Request $request, TodoTask $task)
    {
        $this->authorizeAccess($task);

        $validated = $request->validate($this->rules());
        $validated = $this->normalizeLink($validated);

        $task->update($validated);

        if ($request->wantsJson()) {
            return $this->jsonOk(__('Task updated.'));
        }

        return redirect()->route('todo.index')
            ->with('success', __('Task updated.'));
    }

    public function complete(Request $request, TodoTask $task)
    {
        $this->authorizeAccess($task);

        $task->update([
            'status' => TodoTask::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return $this->jsonOk(__('Task completed.'));
        }

        return redirect()->route('todo.index')
            ->with('success', __('Task completed.'));
    }

    public function reopen(Request $request, TodoTask $task)
    {
        $this->authorizeAccess($task);

        $task->update([
            'status' => TodoTask::STATUS_ACTIVE,
            'completed_at' => null,
        ]);

        if ($request->wantsJson()) {
            return $this->jsonOk(__('Task reopened.'));
        }

        return redirect()->route('todo.index')
            ->with('success', __('Task reopened.'));
    }

    public function destroy(Request $request, TodoTask $task)
    {
        $this->authorizeAccess($task);

        $task->delete();

        if ($request->wantsJson()) {
            return $this->jsonOk(__('Task deleted.'));
        }

        return redirect()->route('todo.index')
            ->with('success', __('Task deleted.'));
    }

    private function gatherData(): array
    {
        $now = now();

        $groups = [
            TodoTask::BUCKET_OVERDUE => collect(),
            TodoTask::BUCKET_TODAY => collect(),
            TodoTask::BUCKET_THIS_MONTH => collect(),
            TodoTask::BUCKET_THIS_YEAR => collect(),
            TodoTask::BUCKET_NO_DEADLINE => collect(),
        ];

        $active = $this->baseQuery()
            ->active()
            ->orderBy('deadline_date')
            ->orderBy('created_at')
            ->get();

        foreach ($active as $task) {
            $groups[TodoTask::bucketKey($task->deadline_date, $task->deadline_granularity, $now)]->push($task);
        }

        $completed = $this->baseQuery()
            ->completed()
            ->orderByDesc('completed_at')
            ->get();

        return compact('groups', 'completed', 'active');
    }

    private function jsonOk(string $message)
    {
        return response()->json(['ok' => true, 'message' => $message]);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'deadline_granularity' => ['nullable', 'string', Rule::in([
                TodoTask::GRANULARITY_DAY,
                TodoTask::GRANULARITY_WEEK,
                TodoTask::GRANULARITY_MONTH,
                TodoTask::GRANULARITY_YEAR,
            ])],
            'deadline_date' => ['nullable', 'date'],
            'priority' => ['required', 'string', Rule::in([
                TodoTask::PRIORITY_LOW,
                TodoTask::PRIORITY_MEDIUM,
                TodoTask::PRIORITY_HIGH,
            ])],
            'linkable_type' => ['nullable', 'string', 'max:255', Rule::in(array_values(TodoTask::LINKABLE_CLASS_MAP))],
            'linkable_id' => ['nullable', 'integer'],
            'link_label' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function normalizeLink(array $validated): array
    {
        $hasLink = !empty($validated['linkable_type']) && !empty($validated['linkable_id']);

        if (!$hasLink) {
            $validated['linkable_type'] = null;
            $validated['linkable_id'] = null;
            $validated['link_label'] = null;
            $validated['link_url'] = null;
        }

        return $validated;
    }
}
