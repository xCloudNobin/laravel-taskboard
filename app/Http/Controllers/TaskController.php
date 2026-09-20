<?php

namespace App\Http\Controllers;

use App\Jobs\RecordTaskActivity;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $query = Task::query()->with('project');

        if ($request->filled('status') && in_array($request->input('status'), Task::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && in_array($request->input('priority'), Task::PRIORITIES, true)) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', (int) $request->input('project_id'));
        }

        if ($request->filled('q')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('title', 'like', '%'.$request->input('q').'%')
                    ->orWhere('description', 'like', '%'.$request->input('q').'%');
            });
        }

        return view('tasks.index', [
            'tasks' => $query->latest()->paginate(10)->withQueryString(),
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('tasks.create', [
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Task::PRIORITIES)],
        ]);

        $task = Task::query()->create($data);

        RecordTaskActivity::dispatch(
            $task->project_id,
            $task->id,
            RecordTaskActivity::ACTION_CREATED,
            $this->context($task),
        );

        return redirect()->route('tasks.show', $task)
            ->with('status', 'Task created.');
    }

    public function show(Task $task): View
    {
        return view('tasks.show', [
            'task' => $task->load('project'),
        ]);
    }

    public function edit(Task $task): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'projects' => Project::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', Task::PRIORITIES)],
        ]);

        $task->update($data);

        RecordTaskActivity::dispatch(
            $task->project_id,
            $task->id,
            RecordTaskActivity::ACTION_UPDATED,
            $this->context($task),
        );

        return redirect()->route('tasks.show', $task)
            ->with('status', 'Task updated.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        RecordTaskActivity::dispatch(
            $task->project_id,
            $task->id,
            RecordTaskActivity::ACTION_DELETED,
            $this->context($task),
        );

        $task->delete();

        return redirect()->route('tasks.index')->with('status', 'Task deleted.');
    }

    /**
     * Snapshot non-sensitive task data for the background activity job.
     *
     * @return array<string, mixed>
     */
    private function context(Task $task): array
    {
        return [
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
        ];
    }
}
