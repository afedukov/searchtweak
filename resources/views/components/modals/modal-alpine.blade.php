@props(['id', 'maxWidth', 'var' => 'show'])

@php
	$id = $id ?? md5(mt_rand());

	$maxWidth = [
		'sm' => 'sm:max-w-sm',
		'md' => 'sm:max-w-md',
		'lg' => 'sm:max-w-lg',
		'xl' => 'sm:max-w-xl',
		'2xl' => 'sm:max-w-2xl',
		'3xl' => 'sm:max-w-3xl',
		'4xl' => 'sm:max-w-4xl',
		'5xl' => 'sm:max-w-5xl',
		'6xl' => 'sm:max-w-6xl',
		'7xl' => 'sm:max-w-7xl',
		'8xl' => 'sm:max-w-8xl',
		'9xl' => 'sm:max-w-9xl',
		'full' => 'sm:max-w-full',
	][$maxWidth ?? '2xl'];
@endphp

<div
		x-on:close.stop="{{ $var }} = false"
		x-on:keydown.escape.window="{{ $var }} = false"
		x-show="{{ $var }}"
		id="{{ $id }}"
		class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
		style="display: none;"
>
	<div x-show="{{ $var }}" class="fixed inset-0 transform transition-all" x-on:click="{{ $var }} = false" x-transition:enter="ease-out duration-300"
		 x-transition:enter-start="opacity-0"
		 x-transition:enter-end="opacity-100"
		 x-transition:leave="ease-in duration-200"
		 x-transition:leave-start="opacity-100"
		 x-transition:leave-end="opacity-0">
		<div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
	</div>

	<div x-show="{{ $var }}" class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto"
		 x-trap.inert.noscroll="{{ $var }}"
		 x-transition:enter="ease-out duration-300"
		 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
		 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
		 x-transition:leave="ease-in duration-200"
		 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
		 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
		{{ $slot }}
	</div>
</div>
