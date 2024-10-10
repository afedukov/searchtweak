@props(['active' => false])
<li>
	<a {{ $attributes->class([
    	'block py-2 pl-3 pr-4',
		"text-white bg-indigo-700 rounded lg:bg-transparent lg:text-indigo-700 lg:p-0 dark:text-white" => $active,
		"text-gray-700 border-b border-gray-100 hover:bg-gray-50 lg:hover:bg-transparent lg:border-0 lg:hover:text-indigo-700 lg:p-0 dark:text-gray-400 lg:dark:hover:text-white dark:hover:bg-gray-700 dark:hover:text-white lg:dark:hover:bg-transparent dark:border-gray-700" => !$active,
	])->merge() }}>
		{{ $slot }}
	</a>
</li>
