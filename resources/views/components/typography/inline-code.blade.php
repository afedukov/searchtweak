@props(['value'])

<span {{ $attributes->merge(['class' => 'bg-gray-100 text-rose-500 border border-gray-200 dark:border-gray-600 text-sm font-normal font-mono px-2 py-0.5 rounded dark:bg-gray-700 dark:text-rose-400']) }}>{{ $value ?? $slot }}</span>
