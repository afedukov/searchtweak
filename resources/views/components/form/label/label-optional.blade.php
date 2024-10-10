@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium mb-1']) }}>
	{{ $value ?? $slot }}
	<span class="text-gray-400 dark:text-gray-500">
		{{ __('Optional') }}
	</span>
</label>
