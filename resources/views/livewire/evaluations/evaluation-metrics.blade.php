<div class="flex flex-wrap gap-3">
	@foreach ($evaluation->metrics as $metric)
		<x-metrics.evaluation-metric
				wire:key="evaluation-metric-{{ $metric->id }}"
				:metric="$metric"
				:keywords-count="$evaluation->keywords_count"
				:change="$metric->getChange(Auth::user()->currentTeam->baseline)"
		/>
	@endforeach
</div>
