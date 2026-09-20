@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-4 text-2xl font-semibold">Create account</h1>
        <form method="POST" action="{{ route('register') }}" class="space-y-4 rounded border bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Create account</button>
            <p class="text-center text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Sign in</a></p>
        </form>
    </div>
@endsection