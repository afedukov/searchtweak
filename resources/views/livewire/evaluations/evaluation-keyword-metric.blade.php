<div>
	<x-metrics.metric
			:value="$value"
			:displayName="$scorer->getDisplayName($metric->num_results)"
			:briefDescription="$scorer->getBriefDescription()"
			:scaleType="$scorer->getScale()->getType()"
	/>
</div>
