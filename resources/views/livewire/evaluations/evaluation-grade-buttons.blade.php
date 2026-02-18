<div>
	<div class="flex items-center gap-1 whitespace-nowrap">

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
				<li>
					<div class="flex items-center gap-1">
						<x-dynamic-component :component="$evaluation->getScale()->getScaleBadgeComponent()" :grade="$feedback->grade" size="sm" />

						<span class="text-xs whitespace-nowrap">
							@if ($feedback->judge_id)
								<span class="text-blue-500 dark:text-blue-400">{{ $feedback->judge?->name ?? 'Removed Judge' }}</span>
								<span class="inline-flex items-center text-[10px] leading-none font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white ml-1">AI</span>
							@else
								{{ $feedback->user?->name ?? 'Removed User' }}
							@endif
						</span>
					</div>
					@if ($feedback->reason)
						<p class="ml-6 mt-0.5 text-xs text-gray-400 dark:text-gray-500 italic max-w-[250px]">{{ Str::limit($feedback->reason, 150) }}</p>
					@endif
				</li>
			@endforeach
		</ul>
	</div>
</div>
