@props(['id'])

@php
	$id = $id ?? unique_key();
@endphp

<div x-data="{ open: false }" class="inline-block" id="{{ $id }}">
	<!-- Button -->
	{{ $button }}

	<!-- Modal backdrop -->
	<div
			class="fixed inset-0 bg-slate-900 bg-opacity-30 z-50 transition-opacity"
			x-show="open"
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0"
			x-transition:enter-end="opacity-100"
			x-transition:leave="transition ease-out duration-100"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			aria-hidden="true"
			x-cloak
	></div>
	<!-- Modal dialog -->
	<div
			class="fixed inset-0 z-50 overflow-hidden flex items-start top-20 mb-4 justify-center px-4 sm:px-6"
			role="dialog"
			aria-modal="true"
			x-show="open"
			x-transition:enter="transition ease-in-out duration-200"
			x-transition:enter-start="opacity-0 translate-y-4"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-in-out duration-200"
			x-transition:leave-start="opacity-100 translate-y-0"
			x-transition:leave-end="opacity-0 translate-y-4"
			x-cloak
	>
		<div
				class="bg-white dark:bg-slate-800 border border-transparent dark:border-slate-700 overflow-auto max-w-2xl w-full max-h-full rounded shadow-lg relative"
				@click.outside="open = false"
				@keydown.escape.window="open = false"
		>
			<!-- Close button -->
			<button @click.prevent="open = false" class="absolute top-0 right-0 p-3 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>

			<div class="p-4">
				{{ $slot }}
			</div>
		</div>
	</div>
</div>
