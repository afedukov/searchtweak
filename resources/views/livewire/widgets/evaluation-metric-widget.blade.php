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

					<!-- Metric Value -->
					<x-metrics.metric-value :scaleType="$scaleType" :metric="$metric" />
				</div>

				<div class="flex items-baseline gap-1">
					<livewire:evaluations.evaluation-archived-badge :evaluation="$metric->evaluation" key="evaluation-archived-badge-{{ $metric->evaluation->id }}" />
					<livewire:evaluations.evaluation-status :evaluation="$metric->evaluation" key="evaluation-status-{{ $metric->evaluation->id }}" />
				</div>
			</div>
		</div>
		<div class="grow">
			@php($id = unique_key())
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
