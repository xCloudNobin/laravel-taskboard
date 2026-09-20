@php
    $styles = [
        'todo' => 'bg-slate-100 text-slate-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'done' => 'bg-green-100 text-green-700',
        'active' => 'bg-green-100 text-green-700',
        'archived' => 'bg-slate-200 text-slate-600',
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-700',
        'high' => 'bg-red-100 text-red-700',
    ];
    $labels = [
        'todo' => 'To do',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'active' => 'Active',
        'archived' => 'Archived',
        'low' => 'Low priority',
        'medium' => 'Medium priority',
        'high' => 'High priority',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $styles[$value] ?? 'bg-slate-100 text-slate-700' }}">
    {{ $labels[$value] ?? ucfirst((string) $value) }}
</span>