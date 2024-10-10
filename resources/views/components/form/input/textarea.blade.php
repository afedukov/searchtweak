@props(['disabled' => false])

<textarea
		{{ $disabled ? 'disabled' : '' }}
		{!! $attributes->merge(['rows' => '4', 'class' => 'block p-2.5 w-full text-sm text-gray-600 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:text-gray-200 dark:focus:ring-blue-500 dark:focus:border-blue-500']) !!}
></textarea>
