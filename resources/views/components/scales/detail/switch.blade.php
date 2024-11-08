@props(['scaleKey', 'selected' => false])
@php
	$activeColors = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\DetailScale::V_1 => 'bg-red-800 dark:bg-red-900',
		\App\Services\Scorers\Scales\DetailScale::V_2 => 'bg-red-700 dark:bg-red-800',
		\App\Services\Scorers\Scales\DetailScale::V_3 => 'bg-red-600 dark:bg-red-700',
		\App\Services\Scorers\Scales\DetailScale::V_4 => 'bg-red-500 dark:bg-red-600',
		\App\Services\Scorers\Scales\DetailScale::V_5 => 'bg-red-400 dark:bg-red-500',
		\App\Services\Scorers\Scales\DetailScale::V_6 => 'bg-green-400 dark:bg-green-500',
		\App\Services\Scorers\Scales\DetailScale::V_7 => 'bg-green-500 dark:bg-green-600',
		\App\Services\Scorers\Scales\DetailScale::V_8 => 'bg-green-600 dark:bg-green-700',
		\App\Services\Scorers\Scales\DetailScale::V_9 => 'bg-green-700 dark:bg-green-800',
		\App\Services\Scorers\Scales\DetailScale::V_10 => 'bg-green-800 dark:bg-green-900',
	};

	$hoverColors = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\DetailScale::V_1 => 'hover:bg-red-900 dark:hover:bg-red-800',
		\App\Services\Scorers\Scales\DetailScale::V_2 => 'hover:bg-red-800 dark:hover:bg-red-700',
		\App\Services\Scorers\Scales\DetailScale::V_3 => 'hover:bg-red-700 dark:hover:bg-red-600',
		\App\Services\Scorers\Scales\DetailScale::V_4 => 'hover:bg-red-600 dark:hover:bg-red-500',
		\App\Services\Scorers\Scales\DetailScale::V_5 => 'hover:bg-red-500 dark:hover:bg-red-400',
		\App\Services\Scorers\Scales\DetailScale::V_6 => 'hover:bg-green-500 dark:hover:bg-green-400',
		\App\Services\Scorers\Scales\DetailScale::V_7 => 'hover:bg-green-600 dark:hover:bg-green-500',
		\App\Services\Scorers\Scales\DetailScale::V_8 => 'hover:bg-green-700 dark:hover:bg-green-600',
		\App\Services\Scorers\Scales\DetailScale::V_9 => 'hover:bg-green-800 dark:hover:bg-green-700',
		\App\Services\Scorers\Scales\DetailScale::V_10 => 'hover:bg-green-900 dark:hover:bg-green-800',
	};
@endphp

<button {{ $attributes->class([$hoverColors, $activeColors => $selected, 'bg-gray-200 dark:bg-gray-700' => !$selected]) }}>
	{{ $slot }}
</button>
