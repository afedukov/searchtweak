@props(['count' => 0, 'textSize' => 'sm'])

<!-- Notification badge -->
<div {{ $attributes->class(['text-xs' => $textSize === 'md', 'text-[10px]' => $textSize === 'sm'])->merge(['class' => 'absolute inline-flex items-center justify-center w-5 h-5 font-bold text-white bg-red-500 border-2 border-white rounded-full -top-2 -end-3 dark:border-gray-900 z-20']) }}>
	{{ $count }}
</div>
