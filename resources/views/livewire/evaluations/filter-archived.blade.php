<div class="relative flex">

	<div class="inline-flex rounded-md" role="group">
		<div class="flex">
			<input type="radio" wire:model.live="filter" key="archived-all" name="filter-archived" id="archived-all" value="all" class="hidden peer" />
			<label for="archived-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-2 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
				All
			</label>
		</div>

		<div class="flex">
			<input type="radio" wire:model.live="filter" key="archived-current" name="filter-archived" id="archived-current" value="current" class="hidden peer" />
			<label for="archived-current" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-2 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
				Current
			</label>
		</div>

		<div class="flex">
			<input type="radio" wire:model.live="filter" key="archived-archived" name="filter-archived" id="archived-archived" value="archived" class="hidden peer" />
			<label for="archived-archived" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-2 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
				Archived
			</label>
		</div>
	</div>

</div>
