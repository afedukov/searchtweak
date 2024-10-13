@props(['scaleKey', 'shortcut' => null])
@php
	$color = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\GradedScale::POOR => 'bg-red-500 dark:bg-red-600 hover:bg-red-700 dark:hover:bg-red-800',
		\App\Services\Scorers\Scales\GradedScale::FAIR => 'bg-amber-500 dark:bg-amber-600 hover:bg-amber-700 dark:hover:bg-amber-800',
		\App\Services\Scorers\Scales\GradedScale::GOOD => 'bg-lime-500 dark:bg-lime-600 hover:bg-lime-700 dark:hover:bg-lime-800',
		\App\Services\Scorers\Scales\GradedScale::PERFECT => 'bg-green-500 dark:bg-green-600 hover:bg-green-700 dark:hover:bg-green-800',
	};
@endphp

<button
		{{ $attributes->merge(['class' => 'w-36 ' . $color]) }}
		x-data="{ active: false }"
		:class="{ 'scale-110': active }"
		@click="active = true; setTimeout(() => active = false, 200);"
		@if ($shortcut !== null)
			x-on:keydown.window="
				if ($event.key === '{{ $shortcut }}') {
					$event.preventDefault();
					$el.click();
				}
			"
		@endif
>
	@if ($shortcut !== null)
		<kbd class="px-1.5 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-md dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">{{ $shortcut }}</kbd>
	@endif
	{{ $slot }}
</button>
