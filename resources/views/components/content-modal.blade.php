@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
	<div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
		<div class="sm:flex sm:items-start">
			@isset($icon)
				<div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-900/20 sm:mx-0 sm:h-10 sm:w-10">
					{{ $icon }}
				</div>
			@endisset

			<div class="mt-3 text-left sm:mt-0 sm:ml-4">
				<h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">
					{{ $title }}
				</h3>

				<div class="mt-2">
					{{ $content }}
				</div>
			</div>
		</div>
	</div>

	@isset($footer)
		<div class="flex flex-row justify-end px-6 py-4 bg-slate-100 dark:bg-slate-900/20 text-right">
			{{ $footer }}
		</div>
	@endisset
</x-modal>
