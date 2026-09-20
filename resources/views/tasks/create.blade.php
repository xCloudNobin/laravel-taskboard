@extends('layouts.app')

@section('title', 'New task')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-4 text-2xl font-semibold">New task</h1>
        <form method="POST" action="{{ route('tasks.store') }}" class="space-y-4 rounded border bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="title" class="block text-sm font-medium">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="project_id" class="block text-sm font-medium">Project</label>
                    <select id="project_id" name="project_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        <option value="">Select a project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((int) old('project_id', request('project_id')) === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium">Status</label>
                    <select id="status" name="status" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @foreach (['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'todo') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="priority" class="block text-sm font-medium">Priority</label>
                    <select id="priority" name="priority" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Save task</button>
                <a href="{{ route('tasks.index') }}" class="px-4 py-2 text-sm text-slate-500 hover:underline">Cancel</a>
            </div>
        </form>
    </div>
@endsection