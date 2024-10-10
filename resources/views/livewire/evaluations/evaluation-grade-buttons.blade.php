<div>
	<div class="flex items-center gap-1">

		<x-scales.scale-switch :snapshot="$snapshot" :scale="$evaluation->getScale()" :selected="$grade" />

		@if ($grade !== null && !$evaluation->isFinished())
			<button
					class="btn ml-2 px-3 py-1 font-medium rounded-lg text-xs text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
					wire:click="resetGrade('{{ $snapshot->id }}')"
					wire:loading.attr="disabled"
			>
				Reset
			</button>
		@endif
	</div>
	<div class="text-left">
		<ul class="mt-4">
			@foreach ($feedbacks as $feedback)
				<li class="flex items-center gap-1">
					<x-dynamic-component :component="$evaluation->getScale()->getScaleBadgeComponent()" :grade="$feedback->grade" size="sm" />

					<span class="text-xs whitespace-nowrap">
						{{ $feedback->user?->name ?? 'Removed User' }}
					</span>
				</li>
			@endforeach
		</ul>
	</div>
</div>
