@props(['removable' => false])

<div class="relative inline-flex" x-data="{ open: false }">
	<button
			class="rounded-full"
			:class="open ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300': 'text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300'"
			aria-haspopup="true"
			@click.prevent="open = !open"
			:aria-expanded="open"
	>
		<span class="sr-only">Menu</span>
		<svg class="w-8 h-8 fill-current" viewBox="0 0 32 32">
			<circle cx="16" cy="16" r="2" />
			<circle cx="10" cy="16" r="2" />
			<circle cx="22" cy="16" r="2" />
		</svg>
	</button>
	<div
			class="origin-top-right z-10 absolute top-full right-0 min-w-36 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 py-1.5 rounded shadow-lg overflow-hidden mt-1"
			@click.outside="open = false"
			@keydown.escape.window="open = false"
			x-show="open"
			x-transition:enter="transition ease-out duration-200 transform"
			x-transition:enter-start="opacity-0 -translate-y-2"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-out duration-200"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			x-cloak
	>
		<ul>
			<li>
				<x-dropdown-link wire:click="up" class="font-medium text-sm text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-slate-200 flex py-1 px-3" href="#0" @click="open = false" @focus="open = true" @focusout="open = false">
					<div class="flex gap-1">
						<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
							<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"/>
						</svg>
						{{ __('Up') }}
					</div>
				</x-dropdown-link>
			</li>
			<li>
				<x-dropdown-link wire:click="down" class="font-medium text-sm text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-slate-200 flex py-1 px-3" href="#0" @click="open = false" @focus="open = true" @focusout="open = false">
					<div class="flex gap-1">
						<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
							<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/>
						</svg>
						{{ __('Down') }}
					</div>
				</x-dropdown-link>
			</li>
			<li>
				<x-dropdown-link wire:click="detach" class="font-medium text-sm text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-slate-200 flex py-1 px-3" href="#0" @click="open = false" @focus="open = true" @focusout="open = false">
					<div class="flex gap-1">
						<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
							<path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
							<path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
						</svg>
						{{ __('Hide') }}
					</div>
				</x-dropdown-link>
			</li>
			@if ($removable)
				<li>
					<a wire:click="remove" class="border-t block w-full px-4 py-2 text-start leading-5 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out font-medium text-sm text-rose-500 hover:text-rose-500" href="#0" @click="open = false" @focus="open = true" @focusout="open = false">
						{{ __('Remove') }}
					</a>
				</li>
			@endif
		</ul>
	</div>
</div>
