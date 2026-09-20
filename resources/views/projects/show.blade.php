@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $project->name }}</h1>
            <p class="mt-1 text-slate-600">{{ $project->description }}</p>
            <p class="mt-1 text-sm text-slate-400">Status: @include('partials.badge', ['value' => $project->status]) · {{ $project->tasks_count }} tasks</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('projects.edit', $project) }}" class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Edit project</a>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and all its tasks?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>

    <div class="mb-4">
        <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">New task in this project</a>
    </div>

    <div class="overflow-hidden rounded border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Title</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Priority</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    <tr>
                        <td class="px-4 py-3 font-medium"><a href="{{ route('tasks.show', $task) }}" class="hover:text-blue-600">{{ $task->title }}</a></td>
                        <td class="px-4 py-3">@include('partials.badge', ['value' => $task->status])</td>
                        <td class="px-4 py-3">@include('partials.badge', ['value' => $task->priority])</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('tasks.edit', $task) }}" class="text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No tasks in this project yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>
@endsection