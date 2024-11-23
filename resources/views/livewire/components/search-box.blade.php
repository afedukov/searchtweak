<div class="relative" x-data="{ query: $wire.entangle('query') }">
	<div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
		<svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
			<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
		</svg>
	</div>
	<input
			wire:model.live.debounce.750ms="query"
			type="text"
			@isset($placeholder)placeholder="{{ $placeholder }}" @endisset
			class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-md w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
	/>
	<div x-show="query" class="absolute inset-y-0 right-0 flex items-center pr-2 pt-0.5">
		<button type="button" @click="query = ''" class="focus:outline-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 rounded-md py-0.5 px-1">
			<svg class="w-5 h-5 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
				<path fill-rule="evenodd" d="M6.707 4.293a1 1 0 00-1.414 1.414L8.586 9l-3.293 3.293a1 1 0 101.414 1.414L10 10.414l3.293 3.293a1 1 0 001.414-1.414L11.414 9l3.293-3.293a1 1 0 00-1.414-1.414L10 7.586 6.707 4.293z" clip-rule="evenodd" />
			</svg>
		</button>
	</div>
</div>
