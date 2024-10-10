@props(['size' => 'md', 'value' => null])

<span {{ $attributes->class(['w-4 h-4 text-[10px]' => $size === 'sm', 'w-6 h-6 text-sm' => $size === 'md'])->merge(['class' => 'inline-flex items-center justify-center me-2 font-semibold rounded-full']) }}>
	{{ $value ?? $slot }}
</span>
