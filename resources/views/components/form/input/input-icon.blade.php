@props(['disabled' => false, 'icon' => 'fa-solid fa-envelope'])

<div class="relative">
	<div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
		<i class="{{ $icon }} text-gray-500 dark:text-gray-400"></i>
	</div>
	<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'mt-1 block ps-10 p-2.5 w-full text-sm text-gray-900 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500']) !!}>
</div>
