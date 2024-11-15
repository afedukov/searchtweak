<div
		x-data="{ expand: $persist(false).as('metric-expanded-@js($metric->id)') }"
		:class="expand ? 'md:col-span-full' : 'md:col-span-6'"
		class="flex flex-col col-span-full bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700"
>
	<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex gap-3 justify-between items-center">
		<!-- Metric Title -->
		<div class="flex items-center gap-3">
			<h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $name }}</h2>
			<div class="text-gray-500 dark:text-gray-300">{{ $description }}</div>
		</div>

		<div class="flex gap-1">
			<!-- Metric Add To Dashboard Button -->
			<div class="relative inline">
				<button
						data-popover-target="attach-metric-{{ $metric->id }}"
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

				<x-tooltip id="attach-metric-{{ $metric->id }}" with-arrow>
					<span class="whitespace-nowrap">
						@if ($attached)
							Remove from Dashboard
						@else
							Add to Dashboard
						@endif
					</span>
				</x-tooltip>
			</div>

			<!-- Metric Expand Button -->
			<div class="relative inline">
				<button
						data-popover-target="expand-metric-{{ $metric->id }}"
						class="rounded-full"
						:class="expand ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300': 'text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300'"
						aria-haspopup="true"
						@click.prevent="expand = !expand"
						:aria-expanded="expand"
				>
					<svg class="w-7 h-7 fill-current p-1" viewBox="0 0 24 24">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H4m0 0v4m0-4 5 5m7-5h4m0 0v4m0-4-5 5M8 20H4m0 0v-4m0 4 5-5m7 5h4m0 0v-4m0 4-5-5"/>
					</svg>
				</button>

				<x-tooltip id="expand-metric-{{ $metric->id }}" with-arrow>
					<span class="whitespace-nowrap" x-text="expand ? 'Collapse' : 'Expand'"></span>
				</x-tooltip>
			</div>
		</div>
	</header>
	<div class="px-5 py-3">
		<div class="flex items-start">
			<div class="mr-2 tabular-nums">

				<!-- Metric Value -->
				<x-metrics.metric-value :scaleType="$scaleType" :metric="$metric" />
			</div>
		</div>
	</div>
	<div class="grow">
		<div class="h-[120px]">
			<canvas
					id="metric-card-id-{{ $metric->id }}"
					wire:key="metric-card-key-{{ $metric->id }}-{{ $attached }}"
					data-metric-card="{{ $metric->id }}"
					data-metric-values="{{ json_encode($metric->getLastValues()) }}"
					width="500"
					height="120"
			></canvas>
		</div>
	</div>
</div>
