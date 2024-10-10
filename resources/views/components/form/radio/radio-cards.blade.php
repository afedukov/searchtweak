@props(['cols' => 3])

<ul {{ $attributes->merge(['class' => "grid w-full gap-4 md:grid-cols-{$cols}"]) }}>
	{{ $slot }}
</ul>
