@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-4 text-2xl font-semibold">Sign in</h1>
        <form method="POST" action="{{ route('login') }}" class="space-y-4 rounded border bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div class="flex items-center gap-2">
                <input id="remember" type="checkbox" name="remember" class="rounded">
                <label for="remember" class="text-sm">Remember me</label>
            </div>
            <button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Sign in</button>
            <p class="text-center text-sm text-slate-500">No account? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Create one</a></p>
        </form>
    </div>
@endsection