@extends('layouts.app')

@section('title', $task->title)

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $task->title }}</h1>
            <p class="mt-1 text-slate-600">{{ $task->description ?: 'No description.' }}</p>
            <p class="mt-1 text-sm text-slate-400">
                Project:
                @if ($task->project)
                    <a href="{{ route('projects.show', $task->project) }}" class="text-blue-600 hover:underline">{{ $task->project->name }}</a>
                @else
                    <span>—</span>
                @endif
                · Status: @include('partials.badge', ['value' => $task->status])
                · Priority: @include('partials.badge', ['value' => $task->priority])
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('tasks.edit', $task) }}" class="rounded bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Edit task</a>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Delete</button>
            </form>
        </div>
    </div>
@endsection