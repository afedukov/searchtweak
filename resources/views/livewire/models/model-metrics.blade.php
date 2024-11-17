<div class="flex flex-wrap gap-3">
	@forelse ($model->getMetrics() as $modelMetric)
		<x-metrics.evaluation-metric
				wire:key="{{ $modelMetric->getId() }}"
				:metric="$modelMetric->getLastMetric()"
				:keywords-count="$modelMetric->getKeywordsCount()"
				:change="$modelMetric->getLastMetric()?->getChange(Auth::user()->currentTeam->baseline)"
		/>
	@empty
		<span class="text-xs text-gray-400 dark:text-gray-500">
			{{ __('No metrics') }}
		</span>
	@endforelse
</div>
