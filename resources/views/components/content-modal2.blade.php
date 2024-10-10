@props(['id' => null, 'maxWidth' => null, 'toggle' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
	<div class="bg-white dark:bg-slate-800">

		<!-- Modal header -->
		<div class="flex items-center justify-between pb-3 border-b rounded-t dark:border-gray-600 px-4 pt-5 sm:p-6 sm:pb-4">
			<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
				{{ $title }}
			</h3>
			<button type="button" wire:click="$toggle('{{ $toggle }}')" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
				<svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
				</svg>
				<span class="sr-only">Close modal</span>
			</button>
		</div>

		<!-- Modal body -->
		<div class="ml-4 text-left p-6 pb-4">
			<div>
				{{ $content }}
			</div>
		</div>

		<!-- Modal footer -->
		@if($footer ?? null)
			<div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600 justify-end text-right">
				{{ $footer }}
			</div>
		@endif

	</div>
</x-modal>
