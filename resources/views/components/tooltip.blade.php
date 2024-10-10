@props(['withArrow' => false])

<div data-popover role="tooltip" {{ $attributes->merge(['class' => 'text-xs absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800']) }}>
	<div class="px-3 py-2">
		{{ $slot }}
	</div>
	@if ($withArrow)
		<div data-popper-arrow></div>
	@endif
</div>
