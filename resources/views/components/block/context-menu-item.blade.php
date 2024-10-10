<li>
	<a {{ $attributes->merge(['href' => '#', 'class' => 'block w-full px-4 py-2 text-start text-sm leading-5 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800']) }}>
		{{ $slot }}
	</a>
</li>
