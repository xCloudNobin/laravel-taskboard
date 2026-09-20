@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Tasks</h1>
        <a href="{{ route('tasks.create') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">New task</a>
    </div>

    <form method="GET" action="{{ route('tasks.index') }}" class="mb-4 flex flex-wrap items-center gap-3 rounded border bg-white p-4 shadow-sm">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tasks…"
               class="rounded border border-slate-300 px-3 py-2 text-sm">
        <select name="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach (['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="priority" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">All priorities</option>
            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="project_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">All projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
        @if (request()->hasAny(['q', 'status', 'priority', 'project_id']))
            <a href="{{ route('tasks.index') }}" class="text-sm text-blue-600 hover:underline">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Title</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Project</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Priority</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    <tr>
                        <td class="px-4 py-3 font-medium"><a href="{{ route('tasks.show', $task) }}" class="hover:text-blue-600">{{ $task->title }}</a></td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($task->project)
                                <a href="{{ route('projects.show', $task->project) }}" class="hover:text-blue-600">{{ $task->project->name }}</a>
                            @endif
                        </td>
                        <td class="px-4 py-3">@include('partials.badge', ['value' => $task->status])</td>
                        <td class="px-4 py-3">@include('partials.badge', ['value' => $task->priority])</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('tasks.edit', $task) }}" class="text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No tasks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>
@endsection