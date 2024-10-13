@props(['scaleKey', 'shortcut' => null])
@php
	$color = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\BinaryScale::IRRELEVANT => 'bg-red-500 dark:bg-red-600 hover:bg-red-600 dark:hover:bg-red-700',
		\App\Services\Scorers\Scales\BinaryScale::RELEVANT => 'bg-green-500 dark:bg-green-600 hover:bg-green-600 dark:hover:bg-green-700',
	};
@endphp

<button
		{{ $attributes->merge(['class' => 'w-40 ' . $color]) }}
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
