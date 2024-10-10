@props(['type' => 'info'])

<div {{ $attributes->class([
		'text-blue-800 bg-blue-50 dark:text-blue-400' => $type === 'info',
		'text-green-800 bg-green-50 dark:text-green-400' => $type === 'success',
		'text-yellow-800 bg-yellow-50 dark:text-yellow-300' => $type === 'warning',
		'text-red-800 bg-red-50 dark:text-red-400' => $type === 'error',
	])->merge(['class' => 'p-4 text-sm rounded-lg dark:bg-gray-800']) }} role="alert"
>
	{{ $slot }}
</div>
