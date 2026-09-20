<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel Taskboard')) — {{ config('app.name', 'Laravel Taskboard') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <header class="bg-white shadow">
        <nav class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="font-semibold text-lg">Taskboard</a>
                @auth
                    <a href="{{ route('tasks.index') }}" class="text-sm hover:text-blue-600">Tasks</a>
                    <a href="{{ route('projects.index') }}" class="text-sm hover:text-blue-600">Projects</a>
                @endauth
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-slate-500" title="Deployment release">release {{ config('app.release', 'dev') }}</span>
                @auth
                    <span class="text-slate-600">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-blue-600 hover:underline">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Sign in</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <strong>The operation could not be completed.</strong>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-6 text-xs text-slate-400">
        {{ config('app.name', 'Laravel Taskboard') }} · Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}
    </footer>
</body>
</html>