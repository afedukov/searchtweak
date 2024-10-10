@props(['date'])

<div class="inline-flex gap-2.5 items-center text-sm px-2.5 py-1.5 rounded-lg border border-gray-250 bg-white dark:bg-gray-700 dark:border-gray-600">
    <svg class="w-3.5 h-3.5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
    </svg>
    <span class="font-semibold text-gray-500 dark:text-gray-300 whitespace-nowrap">
		{{ $date->format('j M Y H:i') }}
	</span>
</div>
