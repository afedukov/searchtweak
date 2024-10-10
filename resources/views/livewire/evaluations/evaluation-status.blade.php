<div class="inline-block">
	<span @class([
				'font-medium px-2.5 py-1 rounded-full text-sm',
				'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' => $color === 'red',
				'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $color === 'green',
				'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $color === 'blue',
			])
	>
		{{ $label }}
	</span>
</div>
