@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Dashboard</h1>
        <a href="{{ route('tasks.create') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">New task</a>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Projects</p>
            <p class="text-3xl font-semibold">{{ $projectCount }}</p>
        </div>
        <div class="rounded border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Tasks</p>
            <p class="text-3xl font-semibold">{{ $taskCount }}</p>
        </div>
        <div class="rounded border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">In progress</p>
            <p class="text-3xl font-semibold">{{ $inProgressCount }}</p>
        </div>
        <div class="rounded border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Done</p>
            <p class="text-3xl font-semibold">{{ $doneCount }}</p>
        </div>
    </div>
@endsection