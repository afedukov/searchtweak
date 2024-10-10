@props(['scaleKey', 'selected' => false])
@php
	$activeColors = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\GradedScale::POOR => 'bg-red-500 dark:bg-red-600',
		\App\Services\Scorers\Scales\GradedScale::FAIR => 'bg-amber-500 dark:bg-amber-600',
		\App\Services\Scorers\Scales\GradedScale::GOOD => 'bg-lime-500 dark:bg-lime-600',
		\App\Services\Scorers\Scales\GradedScale::PERFECT => 'bg-green-500 dark:bg-green-600',
	};

    $hoverColors = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\GradedScale::POOR => 'hover:bg-red-500 dark:hover:bg-red-600',
		\App\Services\Scorers\Scales\GradedScale::FAIR => 'hover:bg-amber-500 dark:hover:bg-amber-600',
		\App\Services\Scorers\Scales\GradedScale::GOOD => 'hover:bg-lime-500 dark:hover:bg-lime-600',
		\App\Services\Scorers\Scales\GradedScale::PERFECT => 'hover:bg-green-500 dark:hover:bg-green-600',
    };
@endphp

<button {{ $attributes->class([$hoverColors, $activeColors => $selected, 'bg-gray-200 dark:bg-gray-700' => !$selected]) }}>
	{{ $slot }}
</button>
