@props(['scaleKey', 'shortcut' => null])
@php
	$color = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\DetailScale::V_1 => 'bg-red-800 dark:bg-red-900 hover:bg-red-900 dark:hover:bg-red-800 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_2 => 'bg-red-700 dark:bg-red-800 hover:bg-red-800 dark:hover:bg-red-700 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_3 => 'bg-red-600 dark:bg-red-700 hover:bg-red-700 dark:hover:bg-red-600 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_4 => 'bg-red-500 dark:bg-red-600 hover:bg-red-600 dark:hover:bg-red-500 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_5 => 'bg-red-400 dark:bg-red-500 hover:bg-red-500 dark:hover:bg-red-400 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_6 => 'bg-green-400 dark:bg-green-500 hover:bg-green-500 dark:hover:bg-green-400 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_7 => 'bg-green-500 dark:bg-green-600 hover:bg-green-600 dark:hover:bg-green-500 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_8 => 'bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_9 => 'bg-green-700 dark:bg-green-800 hover:bg-green-800 dark:hover:bg-green-700 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_10 => 'bg-green-800 dark:bg-green-900 hover:bg-green-900 dark:hover:bg-green-800 text-white',
	};
@endphp

<button
		{{ $attributes->merge(['class' => $color]) }}
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
