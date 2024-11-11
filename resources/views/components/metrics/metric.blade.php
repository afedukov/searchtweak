@props(['value', 'displayName', 'briefDescription' => null, 'scaleType', 'change' => null, 'compact' => null])

<label
		@if ($compact !== null)
			x-data="{ compact: @js($compact) }"
		@endif
		class="items-center justify-between gap-3 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-400 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
		:class="compact ? 'text-xs px-3 py-2' : 'inline-flex text-sm px-4 py-2'"
		@if ($compact === null)
			@click="compact = !compact"
		@endif
>
	<div :class="compact ? 'mb-1' : ''">
		<div class="w-full font-semibold text-gray-500 dark:text-gray-300 whitespace-nowrap">{{ $displayName }}</div>
		<div class="w-full text-xs text-gray-500 dark:text-gray-300" x-show="!compact">{{ $briefDescription }}</div>
	</div>
	<div>
		<div
				@class([
					'font-semibold rounded inline-block px-2.5 py-1.5 whitespace-nowrap flex justify-center items-center',
					'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $scaleType === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
					'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $scaleType === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
					'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $scaleType === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
				])
		>
			<span>
				{{ $value !== null ? number_format($value, 2) : '-' }}
			</span>
			<x-metrics.metric-change :change="$change" />
		</div>
	</div>
</label>
