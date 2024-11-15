<div
		class="flex flex-col col-span-full bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700"
>
	<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex gap-3 justify-between items-center">
		<!-- Card Title -->
		<div class="flex items-center gap-3">
			<h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $model->name }}</h2>
		</div>

		<div class="flex gap-1">
			<!-- Metric Add To Dashboard Button -->
			<div class="relative inline">
				<button
						data-popover-target="attach-model-{{ $model->id }}"
						@class([
        					'rounded-full disabled:opacity-50',
        					'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300' => $attached,
        					'text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300' => !$attached,
						])
						wire:click="attach"
						wire:loading.attr="disabled"
				>
					<div class="w-7 h-7 p-1">
						<i class="fa-solid fa-thumbtack"></i>
					</div>
				</button>

				<x-tooltip id="attach-model-{{ $model->id }}" with-arrow>
					<span class="whitespace-nowrap">
						@if ($attached)
							Remove from Dashboard
						@else
							Add to Dashboard
						@endif
					</span>
				</x-tooltip>
			</div>
		</div>
	</header>

	<div class="px-5 py-3">
		<div class="flex justify-between items-center">

			<div class="flex flex-wrap gap-2 min-h-[34px]">

				@foreach ($metrics as $modelMetric)
					@php
						$metric = $modelMetric->getLastMetric();
						$scaleType = $metric->getScorer()->getScale()->getType();
					@endphp
					<div
							id="value-{{ $model->id }}-{{ $modelMetric->getName() }}"
							@class([
								'text-sm font-semibold px-2.5 py-1.5 rounded min-w-[80px]',
								'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $scaleType === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
								'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $scaleType === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
								'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $scaleType === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
							])
					>
						<span class="inline-block border-3 rounded-full border-{{ $modelMetric->getColor() }} w-3 h-3 mr-2"></span>
						<span class="inline-block">
							{{ number_format($metric->value, 2) }}
						</span>
					</div>
				@endforeach

			</div>

			<div id="model-metric-legend-{{ $model->id }}" class="grow ml-2 mb-1">
				<ul class="flex flex-wrap justify-end"></ul>
			</div>

		</div>
	</div>

	<div class="grow">
		<div class="h-[300px]">
			<canvas
					id="model-canvas-id-{{ $model->id }}"
					wire:key="model-canvas-key-{{ $model->id }}-{{ $attached }}"
					data-model-metrics-card="{{ $model->id }}"
					data-model-metrics-metrics="{{ json_encode($metrics) }}"
					width="500"
					height="300"
			></canvas>
		</div>
	</div>
</div>
