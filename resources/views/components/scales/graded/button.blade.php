@props(['scaleKey'])
@php
	$color = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\GradedScale::POOR => 'bg-red-500 dark:bg-red-600 hover:bg-red-700 dark:hover:bg-red-800',
		\App\Services\Scorers\Scales\GradedScale::FAIR => 'bg-amber-500 dark:bg-amber-600 hover:bg-amber-700 dark:hover:bg-amber-800',
		\App\Services\Scorers\Scales\GradedScale::GOOD => 'bg-lime-500 dark:bg-lime-600 hover:bg-lime-700 dark:hover:bg-lime-800',
		\App\Services\Scorers\Scales\GradedScale::PERFECT => 'bg-green-500 dark:bg-green-600 hover:bg-green-700 dark:hover:bg-green-800',
	};
@endphp

<button {{ $attributes->merge(['class' => 'w-32 ' . $color]) }}>
	{{ $slot }}
</button>
