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
		<ul class="mt-4 space-y-2">
			@foreach ($feedbacks as $feedback)
				<li class="py-0.5">
					<div class="flex items-center gap-2">
						<x-dynamic-component :component="$evaluation->getScale()->getScaleBadgeComponent()" :grade="$feedback->grade" size="sm" />
						@if ($feedback->judge_id)
							<x-block.judge-name
								:judge="$feedback->judge"
								icon-size="sm"
								name-class="text-xs leading-none text-blue-500 dark:text-blue-400"
								class="whitespace-nowrap"
							/>
						@else
							<span class="text-xs whitespace-nowrap leading-none">
								{{ $feedback->user?->name ?? 'Removed User' }}
							</span>
						@endif
					</div>
					@if ($feedback->reason)
						@php($isLongReason = Str::length($feedback->reason) > 150)
						<p x-data="{ expanded: false }" class="ml-12 mt-0.5 text-xs text-gray-400 dark:text-gray-500 italic max-w-[250px]">
							<span
								@if ($isLongReason)
									@click="expanded = !expanded"
								@endif
								class="block text-left w-full"
							>
								<span x-show="!expanded">{{ $isLongReason ? Str::substr($feedback->reason, 0, 150) . '...' : $feedback->reason }}</span>
								<span x-show="expanded" x-cloak>{{ $feedback->reason }}</span>
							</span>
						</p>
					@endif
				</li>
			@endforeach
		</ul>
	</div>
</div>
