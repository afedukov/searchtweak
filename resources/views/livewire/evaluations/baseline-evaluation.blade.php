<div class="flex flex-wrap gap-4 mb-4 px-8 py-4 rounded-lg border-2 border-dashed border-gray-250 dark:border-gray-600 text-sm" id="baseline-evaluation">
	@if ($evaluation)
		<div class="w-full text-xs font-bold text-orange-500 dark:text-orange-400">
			Baseline
		</div>

		<div class="max-w-64 min-w-28 font-medium text-gray-900 dark:text-white">
			<a href="{{ route('evaluation', $evaluation->id) }}">
				<div>
					{{ $evaluation->name }}
				</div>
				<div class="text-sm text-gray-400 dark:text-gray-400">
					{{ $evaluation->description }}
				</div>
			</a>
		</div>

		<div>
			<!-- Evaluation Metrics -->
			<div class="flex flex-wrap gap-3" x-data="{ compact: $persist(false).as('compact-evaluation-baseline') }">
				@foreach ($evaluation->metrics as $metric)
					<x-metrics.evaluation-metric :metric="$metric" :keywords-count="$evaluation->keywords_count" />
				@endforeach
			</div>
		</div>

		<div class="ml-auto flex items-center">
			<a
					href="#"
					class="text-blue-500 hover:underline"
					wire:click="baseline('{{ $evaluation->id }}', false)"
					wire:loading.attr="disabled"
			>
				Reset
			</a>
		</div>
	@endif
</div>
