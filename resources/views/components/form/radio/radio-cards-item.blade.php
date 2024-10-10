@props(['id', 'key', 'name' => '', 'description' => '', 'disabled' => false, 'arrow' => false])

<li>
	<input type="radio" id="{{ $id }}" value="{{ $key }}" class="hidden peer" required {{ $attributes }} @if($disabled)disabled @endif />
	<label for="{{ $id }}" class="@if($disabled)opacity-50 cursor-not-allowed @else cursor-pointer @endif text-sm inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700">
		<div class="block">
			@isset($name)
				<div class="w-full font-semibold">{{ $name }}</div>
			@endisset
			@isset($description)
				<div class="w-full">{{ $description }}</div>
			@endisset
			@isset($slot)
				{{ $slot }}
			@endisset
		</div>
		@if ($arrow)
			<svg class="min-w-5 h-5 ms-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
				<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
			</svg>
		@endif
	</label>
</li>
