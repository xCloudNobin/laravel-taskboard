@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Projects</h1>
        <a href="{{ route('projects.create') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">New project</a>
    </div>

    <form method="GET" action="{{ route('projects.index') }}" class="mb-4 flex flex-wrap items-center gap-3 rounded border bg-white p-4 shadow-sm">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects…"
               class="rounded border border-slate-300 px-3 py-2 text-sm">
        <select name="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach (['active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
        @if (request()->hasAny(['q', 'status']))
            <a href="{{ route('projects.index') }}" class="text-sm text-blue-600 hover:underline">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Description</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Tasks</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($projects as $project)
                    <tr>
                        <td class="px-4 py-3 font-medium"><a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a></td>
                        <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit($project->description, 60) }}</td>
                        <td class="px-4 py-3">{{ $project->tasks_count }}</td>
                        <td class="px-4 py-3">@include('partials.badge', ['value' => $project->status])</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('projects.edit', $project) }}" class="text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No projects found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $projects->links() }}</div>
@endsection