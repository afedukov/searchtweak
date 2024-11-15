<x-widget-layout padding="px-3 py-2">

	<x-slot name="title">
		<!-- Widget Title -->
		<a href="{{ route('model', $model->id) }}" class="hover:bg-slate-100 dark:hover:bg-slate-700 px-3.5 py-2 rounded-md block hover:shadow dark:hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
			<div class="flex items-center gap-3">
				<h2 class="font-semibold text-slate-800 dark:text-slate-100">Model</h2>
			</div>
			<div class="text-xs font-normal text-gray-500 dark:text-gray-300">{{ $model->name }}</div>
		</a>
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
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
			@php($id = unique_key())
			<div class="h-[180px]">
				<canvas
						id="id-{{ $id }}"
						wire:key="key-{{ $id }}"
						data-model-metrics-card="{{ $model->id }}"
						data-model-metrics-metrics="{{ json_encode($metrics) }}"
						width="500"
						height="180"
				></canvas>
			</div>
		</div>
	</div>
</x-widget-layout>
