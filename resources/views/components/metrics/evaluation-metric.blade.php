@props(['metric', 'keywordsCount' => 1, 'change' => null])

@php
	/** @var \App\Models\EvaluationMetric $metric */

	$scorer = $metric->getScorer();
@endphp

<x-metrics.metric
		:value="$metric->value"
		:displayName="$scorer->getDisplayName($metric->num_results, $keywordsCount)"
		:briefDescription="$scorer->getBriefDescription($keywordsCount)"
		:scaleType="$scorer->getScale()->getType()"
		:change="$change"
/>
