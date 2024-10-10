@props(['colorClass'])

<span {{ $attributes->class([$colorClass])->merge(['class' => 'min-h-[26px] inline-flex items-center px-2 py-1 text-xs font-medium rounded']) }}>
	<span class="min-w-6 text-center">{{ $slot }}</span>
</span>
