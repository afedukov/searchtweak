@props(['scaleKey', 'selected' => false])
@php
	$activeColors = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\BinaryScale::IRRELEVANT => 'bg-red-500 dark:bg-red-600',
		\App\Services\Scorers\Scales\BinaryScale::RELEVANT => 'bg-green-500 dark:bg-green-600',
	};

    $hoverColors = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\BinaryScale::IRRELEVANT => 'hover:bg-red-500 dark:hover:bg-red-600',
		\App\Services\Scorers\Scales\BinaryScale::RELEVANT => 'hover:bg-green-500 dark:hover:bg-green-600',
    };
@endphp

<button {{ $attributes->class([$hoverColors, $activeColors => $selected, 'bg-gray-200 dark:bg-gray-700' => !$selected]) }}>
	{{ $slot }}
</button>
