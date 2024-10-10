<button wire:click="$toggle('addWidgetModal')" class="btn bg-indigo-500 hover:bg-indigo-600 text-white">
	<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
		<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
	</svg>
	<span class="hidden xs:block ml-2">Add Widget</span>
</button>

<!-- Add Widget Modal -->
<x-dialog-modal wire:model.live="addWidgetModal">
	<x-slot name="title">
		{{ __('Add Widget') }}
	</x-slot>

	<x-slot name="content">
		<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

			<ul class="grid w-full gap-6 md:grid-cols-2">
				<li>
					<input wire:model="add" type="radio" id="hosting-small" name="hosting" value="evaluation:11" class="hidden peer" required />
					<label for="hosting-small" class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700">
						<div class="block">
							<div class="w-full text-lg font-semibold">Evaluation #1</div>
							<div class="w-full">Good for small websites</div>
						</div>
						<svg class="w-5 h-5 ms-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
							<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
						</svg>
					</label>
				</li>
				<li>
					<input wire:model="add" type="radio" id="hosting-big" name="hosting" value="evaluation:13" class="hidden peer">
					<label for="hosting-big" class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700">
						<div class="block">
							<div class="w-full text-lg font-semibold">Evaluation #2</div>
							<div class="w-full">Good for large websites</div>
						</div>
						<svg class="w-5 h-5 ms-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
							<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
						</svg>
					</label>
				</li>
			</ul>


		</div>
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button wire:click="$toggle('addWidgetModal')" wire:loading.attr="disabled">
			{{ __('Cancel') }}
		</x-secondary-button>

		<x-button class="ms-3" wire:click="addWidget" wire:loading.attr="disabled">
			{{ __('Add Widget') }}
		</x-button>
	</x-slot>
</x-dialog-modal>
