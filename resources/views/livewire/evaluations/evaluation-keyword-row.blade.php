<tbody x-data="{ expand: $persist(false).as('keyword-expanded-@js($keyword->id)') }">
	<tr
			:class="expand ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800'"
			class="cursor-pointer border-b last:border-0 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
			@click.prevent="expand = !expand"
	>
		<td class="px-6 py-4 text-center">
			<!-- Collapse/Expand Row Button -->
			<div class="flex items-center cursor-pointer">
				<svg class="w-3 h-3 shrink-0" :class="expand ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
			</div>
		</td>
		<th scope="row" class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
			<div class="relative inline-flex">
				{{ $keyword->keyword }}
				<!-- Non-graded Snapshots Count Badge -->
				<livewire:evaluations.evaluation-keyword-count-badge :keyword="$keyword" key="evaluation-keyword-count-badge-{{ $keyword->id }}" />

				@if ($keyword->isFailed())
					<!-- Failed Keyword Badge -->
					<span class="ml-2 font-medium px-2.5 py-1 rounded-full text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
						Failed
					</span>
				@endif
			</div>
		</th>
		<td class="px-6 py-4">
			<!-- Keyword Metrics -->
			<div class="flex flex-wrap gap-3">
				@foreach ($evaluation->metrics as $metric)
					<livewire:evaluations.evaluation-keyword-metric :keyword="$keyword" :metric="$metric" key="evaluation-keyword-metric-{{ $keyword->id }}-{{ $metric->id }}" />
				@endforeach
			</div>
		</td>
	</tr>
	<tr x-show="expand">
		<td colspan="3" class="p-0 m-0">
			<x-evaluations.keyword-expanded :evaluation="$evaluation" :keyword="$keyword" />
		</td>
	</tr>
</tbody>
