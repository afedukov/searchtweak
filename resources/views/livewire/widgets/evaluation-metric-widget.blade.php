<x-widget-layout padding="px-3 py-2">

	<x-slot name="title">
		<!-- Metric Title -->
		<a href="{{ route('evaluation', [$metric->search_evaluation_id]) }}" class="hover:bg-slate-100 dark:hover:bg-slate-700 px-3.5 py-2 rounded-md block hover:shadow dark:hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
			<div class="flex items-center gap-3">
				<h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $name }}</h2>
				<div class="text-gray-500 dark:text-gray-300">{{ $description }}</div>
			</div>
			<div class="text-xs font-normal text-gray-500 dark:text-gray-300">{{ $metric->evaluation->name }}</div>
		</a>
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
		<div class="px-5 py-3">
			<div class="flex items-center justify-between">
				<div class="mr-2 tabular-nums">

					<span
							id="metric-value-{{ $metric->id }}"
							@class([
								'text-lg font-semibold px-2.5 py-1.5 rounded inline-block min-w-14 text-center',
								'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $scaleType === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
								'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $scaleType === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
								'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $scaleType === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
							])
					>
						@if ($metric->value !== null)
							{{ number_format($metric->value, 2) }}
						@else
							-
						@endif
					</span>
				</div>

				<livewire:evaluations.evaluation-status :evaluation="$metric->evaluation" wire:key="{{ md5(mt_rand()) }}" />
			</div>
		</div>
		<div class="grow">
			@php($id = md5(mt_rand()))
			<div class="h-[175px]">
				<canvas
						id="id-{{ $id }}"
						wire:key="key-{{ $id }}"
						data-metric-card="{{ $metric->id }}"
						data-metric-values="{{ json_encode($metric->getLastValues()) }}"
						width="500"
						height="175"
				></canvas>
			</div>
		</div>
	</div>
</x-widget-layout>
