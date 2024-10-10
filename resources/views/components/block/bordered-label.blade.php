<div class="inline-flex gap-2.5 items-center text-sm px-2.5 py-1.5 rounded-lg border border-gray-250 bg-white dark:bg-gray-700 dark:border-gray-600">
    <span {{ $attributes->merge(['class' => 'font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap']) }}>
		{{ $slot }}
	</span>
</div>
