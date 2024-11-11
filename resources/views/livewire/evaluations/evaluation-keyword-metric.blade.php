<div class="flex flex-wrap gap-3">
	<x-metrics.metric
			:value="$value"
			:displayName="$scorer->getDisplayName($metric->num_results)"
			:briefDescription="$scorer->getBriefDescription()"
			:scaleType="$scorer->getScale()->getType()"
			compact
	/>
</div>
