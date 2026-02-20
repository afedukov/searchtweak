@props([
    'judgeId' => null,
    'size' => 'md',
    'circle' => false,
])

@php
    $judgeId = (int) ($judgeId ?? 0);

    $palette = [
        'text-sky-500 dark:text-sky-400',
        'text-lime-500 dark:text-lime-400',
        'text-indigo-500 dark:text-indigo-400',
        'text-rose-500 dark:text-rose-400',
        'text-emerald-500 dark:text-emerald-400',
        'text-amber-500 dark:text-amber-400',
        'text-cyan-500 dark:text-cyan-400',
        'text-violet-500 dark:text-violet-400',
        'text-teal-500 dark:text-teal-400',
        'text-orange-500 dark:text-orange-400',
    ];

    $color = $palette[abs($judgeId) % count($palette)];

    $iconSizeClasses = match ($size) {
        'sm' => 'w-4 h-4',
        'lg' => 'w-6 h-6',
        default => 'w-5 h-5',
    };

    $circleSizeClasses = match ($size) {
        'sm' => 'w-8 h-8',
        'lg' => 'w-10 h-10',
        default => 'w-9 h-9',
    };

@endphp

@if ($circle)
    <div {{ $attributes->merge(['class' => "inline-flex items-center justify-center {$circleSizeClasses} rounded-full bg-slate-100 dark:bg-slate-700"]) }}>
        <svg class="{{ $iconSizeClasses }} {{ $color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </div>
@else
    <svg {{ $attributes->merge(['class' => "{$iconSizeClasses} {$color}"]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    </svg>
@endif
