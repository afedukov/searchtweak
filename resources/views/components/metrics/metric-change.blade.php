@props(['change' => null])
@php
	/** @var \App\DTO\MetricChange $change */
@endphp

@if ($change !== null)
	@if ($change->getChange() == 0)
		<span class="whitespace-nowrap max-h-5 text-[11px] py-0.5 px-1.5 rounded ml-2 bg-gray-400 dark:bg-gray-600 text-white">
			0%
		</span>
	@elseif ($change->getChange() > 0)
		<span class="whitespace-nowrap max-h-5 text-[11px] py-0.5 px-1.5 rounded ml-2 bg-green-400 dark:bg-green-600 text-white">
			<i class="fa-solid fa-arrow-up"></i>
			@if ($change->isShowChangeValue())
				{{ $change->getChange() }}%
			@endif
		</span>
	@else
		<span class="whitespace-nowrap max-h-5 text-[11px] py-0.5 px-1.5 rounded ml-2 bg-red-400 dark:bg-red-500 text-white">
			<i class="fa-solid fa-arrow-down"></i>
			@if ($change->isShowChangeValue())
				{{ abs($change->getChange()) }}%
			@endif
		</span>
	@endif
@endif
