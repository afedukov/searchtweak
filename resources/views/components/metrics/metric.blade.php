@props(['value', 'displayName', 'briefDescription', 'scaleType'])

<label class="text-sm inline-flex items-center justify-between gap-3 px-4 py-2 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-400 bg-white dark:bg-gray-700 dark:border-gray-600">
	<div>
		<div class="w-full font-semibold text-gray-500 dark:text-gray-300 whitespace-nowrap">{{ $displayName }}</div>
		<div class="w-full text-xs text-gray-500 dark:text-gray-300">{{ $briefDescription }}</div>
	</div>
	<div>
		<span @class([
			'text-sm font-semibold px-2.5 py-1.5 rounded inline-block min-w-14 text-center',
			'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $scaleType === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
			'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $scaleType === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
			'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $scaleType === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
		])>
			{{ $value !== null ? number_format($value, 2) : '-' }}
		</span>
	</div>
</label>
