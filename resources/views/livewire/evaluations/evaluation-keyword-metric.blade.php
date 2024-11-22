<div x-show="!excludedMetrics.includes({{ $metric->id }})" class="flex flex-wrap mr-3 mb-3">
	<x-metrics.metric
			:value="$value"
			:displayName="$scorer->getDisplayName($metric->num_results)"
			:briefDescription="$scorer->getBriefDescription()"
			:scaleType="$scorer->getScale()->getType()"
			:change="$change"
			compact
	/>
</div>
