@props(['metric', 'keywordsCount' => 1])

@php
	/** @var \App\Models\EvaluationMetric $metric */

	$scorer = $metric->getScorer();
	$value = $metric->value;
@endphp

<x-metrics.metric
		:value="$value"
		:displayName="$scorer->getDisplayName($metric->num_results, $keywordsCount)"
		:briefDescription="$scorer->getBriefDescription($keywordsCount)"
		:scaleType="$scorer->getScale()->getType()"
/>
