<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Project::query()->withCount('tasks')->with('tasks');

        if ($request->filled('status') && in_array($request->input('status'), Project::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('name', 'like', '%'.$request->input('q').'%')
                    ->orWhere('description', 'like', '%'.$request->input('q').'%');
            });
        }

        return view('projects.index', [
            'projects' => $query->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:'.implode(',', Project::STATUSES)],
        ]);

        $project = Project::query()->create($data);

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        $project->loadCount('tasks');

        return view('projects.show', [
            'project' => $project,
            'tasks' => $project->tasks()
                ->when(request('status'), fn ($query) => in_array(request('status'), Task::STATUSES, true)
                    ? $query->where('status', request('status'))
                    : $query)
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', ['project' => $project]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:'.implode(',', Project::STATUSES)],
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }
}
