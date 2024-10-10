@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium mb-1']) }}>
	{{ $value ?? $slot }}
	<i class="fa-solid fa-asterisk text-rose-500 dark:text-rose-400"></i>
</label>
