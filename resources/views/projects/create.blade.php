@extends('layouts.app')

@section('title', 'New project')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-4 text-2xl font-semibold">New project</h1>
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-4 rounded border bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Save project</button>
                <a href="{{ route('projects.index') }}" class="px-4 py-2 text-sm text-slate-500 hover:underline">Cancel</a>
            </div>
        </form>
    </div>
@endsection