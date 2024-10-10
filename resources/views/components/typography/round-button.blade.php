@props(['size' => 'normal'])

@php
	$size = [
		'small' => 'p-1.5',
		'normal' => 'p-2.5',
		'large' => 'p-3.5',
	][$size];
@endphp

<button type="button" {{ $attributes->merge(['class' => 'bg-indigo-500 hover:bg-indigo-600 text-white whitespace-nowrap disabled:opacity-25 font-medium rounded-full text-sm text-center inline-flex items-center ' . $size]) }}>
	{{ $slot }}
</button>
