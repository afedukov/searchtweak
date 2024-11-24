<div class="relative flex">

	<div class="inline-flex rounded-md items-center" role="group">
		<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
			<input type="radio" wire:model.live="status" wire:loading.attr="disabled" key="keywords-status-all" name="filter-all" id="keywords-status-all" value="all" class="hidden peer" />
			<label for="keywords-status-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
				All
			</label>
		</div>

		<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
			<input type="radio" wire:model.live="status" wire:loading.attr="disabled" key="keywords-status-successful" name="filter-successful" id="keywords-status-successful" value="successful" class="hidden peer" />
			<label for="keywords-status-successful" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
				Successful
				<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">{{ $evaluation->successful_keywords }}</span>
			</label>
		</div>

		<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
			<input type="radio" wire:model.live="status" wire:loading.attr="disabled" key="keywords-status-failed" name="filter-failed" id="keywords-status-failed" value="failed" class="hidden peer" />
			<label for="keywords-status-failed" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
				Failed
				<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">{{ $evaluation->failed_keywords }}</span>
			</label>
		</div>
	</div>

</div>
