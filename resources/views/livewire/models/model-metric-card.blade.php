<div
		x-data="{ expand: $persist(false).as('mm-' + @js($metric->getId())) }"
		:class="expand ? 'md:col-span-full' : 'md:col-span-6'"
		class="flex flex-col col-span-full bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700"
>
	<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex gap-3 justify-between items-center">
		<!-- Metric Title -->
		<div class="flex items-center gap-3">
			<h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $metric->getName() }}</h2>
			<div class="text-gray-500 dark:text-gray-300">{{ $metric->getBriefDescription() }}</div>
		</div>

		<div class="flex gap-1">
			<!-- Metric Expand Button -->
			<div class="relative inline">
				<button
						data-popover-target="{{ $metric->getId() }}-expand-tooltip"
						class="rounded-full"
						:class="expand ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300': 'text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300'"
						aria-haspopup="true"
						@click.prevent="expand = !expand"
						:aria-expanded="expand"
				>
					<svg class="w-7 h-7 fill-current p-1" viewBox="0 0 24 24">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
							  d="M8 4H4m0 0v4m0-4 5 5m7-5h4m0 0v4m0-4-5 5M8 20H4m0 0v-4m0 4 5-5m7 5h4m0 0v-4m0 4-5-5"/>
					</svg>
				</button>

				<x-tooltip id="{{ $metric->getId() }}-expand-tooltip" with-arrow>
					<span class="whitespace-nowrap" x-text="expand ? 'Collapse' : 'Expand'"></span>
				</x-tooltip>
			</div>
		</div>
	</header>
	<div class="px-5 py-3">
		<div class="flex items-start">
			<div class="mr-2 tabular-nums">

				<!-- Metric Value -->
				<span
						id="model-metric-value-{{ $metric->getId() }}"
						@class([
							'text-lg font-semibold px-2.5 py-1.5 rounded inline-block min-w-14 text-center',
							'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $metric->getScaleType() === \App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE,
							'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $metric->getScaleType() === \App\Services\Scorers\Scales\GradedScale::SCALE_TYPE,
							'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' => $metric->getScaleType() === \App\Services\Scorers\Scales\DetailScale::SCALE_TYPE,
						])
				>
					{{ number_format($metric->getLastDatasetItem()['value'], 2) }}
				</span>

			</div>
		</div>
	</div>
	<div class="grow">
		@php($id = md5(mt_rand()))
		<div class="h-[200px]">
			<canvas
					id="id-{{ $id }}"
					wire:key="key-{{ $id }}"
					data-model-metric-card="{{ $metric->getId() }}"
					data-model-metric-dataset="{{ json_encode($metric->getDataset()) }}"
					width="500"
					height="200"
			></canvas>
		</div>
	</div>
</div>
