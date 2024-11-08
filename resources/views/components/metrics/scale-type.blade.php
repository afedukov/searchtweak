@props(['scaleType', 'scaleName'])

<div {{ $attributes->merge(['class' => 'inline-block']) }}>
	<span @class([
		'text-xs font-medium me-2 px-2.5 py-0.5 rounded whitespace-nowrap',
		'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $scaleType === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
		'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $scaleType === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
		'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $scaleType === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
	])>
		{{ $scaleName }}
	</span>
</div>
