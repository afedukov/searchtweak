@props(['scaleKey'])
@php
	$color = match ((int) $scaleKey) {
		\App\Services\Scorers\Scales\BinaryScale::IRRELEVANT => 'bg-red-500 dark:bg-red-600 hover:bg-red-600 dark:hover:bg-red-700',
		\App\Services\Scorers\Scales\BinaryScale::RELEVANT => 'bg-green-500 dark:bg-green-600 hover:bg-green-600 dark:hover:bg-green-700',
	};
@endphp

<button {{ $attributes->merge(['class' => 'w-40 ' . $color]) }}>
	{{ $slot }}
</button>
