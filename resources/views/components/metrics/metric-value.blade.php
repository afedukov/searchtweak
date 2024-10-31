@props(['scaleType', 'metric'])
@php
	/** @var \App\Models\EvaluationMetric $metric */
@endphp

<div
	@class([
		'text-lg font-semibold px-2.5 py-1.5 rounded inline-block min-w-14 whitespace-nowrap flex justify-center items-center',
		'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $scaleType === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
		'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $scaleType === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
		'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $scaleType === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
	])
>
	<span id="metric-value-{{ $metric->id }}">
		@if ($metric->value !== null)
			{{ number_format($metric->value, 2) }}
		@else
			-
		@endif
	</span>
	<span id="metric-change-{{ $metric->id }}" class="flex">
		<x-metrics.metric-change :change="$metric->getChange(Auth::user()->currentTeam->baseline)" />
	</span>
</div>
