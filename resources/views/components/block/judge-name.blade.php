@props([
    'judge' => null,
    'iconCircle' => false,
    'iconSize' => 'sm',
    'nameClass' => 'text-sm font-medium dark:text-slate-300 group-hover:text-slate-800 dark:group-hover:text-slate-200',
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <x-block.judge-icon :judge-id="$judge?->id" :size="$iconSize" :circle="$iconCircle" />
    <div class="inline-flex items-center min-w-0 ml-2">
        <span class="truncate {{ $nameClass }}">
            {{ $judge?->name ?? 'Removed Judge' }}
        </span>
    </div>
</div>
