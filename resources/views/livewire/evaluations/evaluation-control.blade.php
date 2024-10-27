<div class="inline-flex gap-2.5 items-center text-sm px-2.5 py-1.5 rounded-lg border border-gray-250 bg-white dark:bg-gray-700 dark:border-gray-600">
	<svg data-popover-target="evaluation-created-{{ $evaluation->id }}" class="w-3.5 h-3.5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
		<path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
	</svg>
	<x-tooltip id="evaluation-created-{{ $evaluation->id }}" with-arrow>
		<span class="whitespace-nowrap">
			<ul>
				<li>
					<span class="font-bold">Created</span>
					<svg class="w-3 h-3 text-green-500 inline" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
						<path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
					</svg>
					<span class="font-medium">
						{{ $evaluation->created_at->toDateTimeString() }}
					</span>
				</li>
				@if ($evaluation->finished_at)
					<li>
						<span class="font-bold">Finished</span>
						<svg class="w-3 h-3 text-blue-500 inline" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
							<path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
						</svg>
						<span class="font-medium">
							{{ $evaluation->finished_at->toDateTimeString() }}
						</span>
					</li>
				@endif
			</ul>
		</span>
	</x-tooltip>
	<span class="font-semibold text-gray-500 dark:text-gray-300 whitespace-nowrap">
		{{ $evaluation->created_at->diffForHumans() }}
	</span>
	<div role="status" x-ref="spinner{{ $evaluation->id }}" @class(['h-7', 'hidden' => !$evaluation->changes_blocked])>
		<svg aria-hidden="true" class="w-7 h-7 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
			<path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
		</svg>
		<span class="sr-only">Loading...</span>
	</div>
	@if (!$evaluation->changes_blocked)
		@if ($evaluation->isPending() && Gate::check('start', $evaluation))
			<button
					data-popover-target="evaluation-start-{{ $evaluation->id }}"
					type="button"
					class="h-7 p-1.5 text-xs font-medium text-center inline-flex items-center text-white bg-green-500 rounded-lg hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-700"
					wire:loading.attr="disabled"
					@click="
						$refs.spinner{{ $evaluation->id }}.classList.remove('hidden');
						$el.classList.add('hidden');
						$wire.start('{{ $evaluation->id }}')"
			>
				<svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"></path>
				</svg>
			</button>
			<x-tooltip id="evaluation-start-{{ $evaluation->id }}" with-arrow>
				<span class="whitespace-nowrap">
					Start Evaluation
				</span>
			</x-tooltip>
		@elseif ($evaluation->isActive() && Gate::check('pause', $evaluation))
			<button
					data-popover-target="evaluation-pause-{{ $evaluation->id }}"
					type="button"
					class="p-1.5 text-xs font-medium text-center inline-flex items-center text-white bg-orange-500 rounded-lg hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-700"
					wire:loading.attr="disabled"
					@click="
						$refs.spinner{{ $evaluation->id }}.classList.remove('hidden');
						$el.classList.add('hidden');
						$wire.pause('{{ $evaluation->id }}')"
			>
				<svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V5.25zm7.5 0A.75.75 0 0115 4.5h1.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H15a.75.75 0 01-.75-.75V5.25z" clip-rule="evenodd"></path>
				</svg>
			</button>
			<x-tooltip id="evaluation-pause-{{ $evaluation->id }}" with-arrow>
				<span class="whitespace-nowrap">
					Pause Evaluation
				</span>
			</x-tooltip>
		@endif
	@endif
</div>
