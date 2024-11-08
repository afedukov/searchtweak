@props(['size' => 'sm', 'grade' => null])
@php
	$sizeClass = match ($size) {
		'sm' => 'text-[10px] leading-[12px] w-[16px]',
		'md' => 'text-[12px] leading-[18px] w-[22px]',
		'lg' => 'text-[12px] leading-[24px] w-[28px]',
	};

	$colorClass = match ($grade) {
		\App\Services\Scorers\Scales\DetailScale::V_1 => 'bg-red-800 dark:bg-red-900 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_2 => 'bg-red-700 dark:bg-red-800 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_3 => 'bg-red-600 dark:bg-red-700 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_4 => 'bg-red-500 dark:bg-red-600 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_5 => 'bg-red-400 dark:bg-red-500 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_6 => 'bg-green-400 dark:bg-green-500 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_7 => 'bg-green-500 dark:bg-green-600 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_8 => 'bg-green-600 dark:bg-green-700 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_9 => 'bg-green-700 dark:bg-green-800 text-white',
		\App\Services\Scorers\Scales\DetailScale::V_10 => 'bg-green-800 dark:bg-green-900 text-white',

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
