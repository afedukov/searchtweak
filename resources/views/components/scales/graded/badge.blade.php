@props(['size' => 'sm', 'grade' => null])
@php
	$sizeClass = match ($size) {
		'sm' => 'text-[10px] leading-[12px] w-[16px]',
		'md' => 'text-[12px] leading-[18px] w-[22px]',
		'lg' => 'text-[12px] leading-[24px] w-[28px]',
	};

    $colorClass = match ($grade) {
		\App\Services\Scorers\Scales\GradedScale::POOR => 'bg-red-500 dark:bg-red-600 text-white',
		\App\Services\Scorers\Scales\GradedScale::FAIR => 'bg-amber-500 dark:bg-amber-600 text-white',
		\App\Services\Scorers\Scales\GradedScale::GOOD => 'bg-lime-500 dark:bg-lime-600 text-white',
		\App\Services\Scorers\Scales\GradedScale::PERFECT => 'bg-green-500 dark:bg-green-600 text-white',
		null => 'bg-gray-200 dark:bg-gray-700 text-slate-400 dark:text-slate-300',
	};
@endphp

<span {{ $attributes->class([$sizeClass, $colorClass])->merge(['class' => 'btn px-1 py-0 font-medium rounded-l focus:outline-none']) }}>
	@if ($grade !== null)
		{{ $grade }}
	@else
		?
	@endif
</span>
