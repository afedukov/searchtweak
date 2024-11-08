@php
	/** @var \App\Services\Scorers\Scales\Scale $scale */
@endphp

<div>
	<x-metrics.scale-type :scaleType="$scale->getType()" :scaleName="$scale->getName()" />
</div>
