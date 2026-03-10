<tbody x-data="{
	expand: $persist(false).as('keyword-expanded-@js($keyword->id)'),
	keywordCopied: false,
	copyKeyword() {
		const value = {{ Js::from($keyword->keyword) }};
		const onSuccess = () => {
			this.keywordCopied = true;
			setTimeout(() => this.keywordCopied = false, 1500);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(value).then(onSuccess);
			return;
		}
		const ta = document.createElement('textarea');
		ta.value = value;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		document.execCommand('copy');
		document.body.removeChild(ta);
		onSuccess();
	}
}">
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
		<th scope="row" class="px-6 py-4 min-w-32 sm:min-w-64 font-semibold text-gray-900 dark:text-white">
			<div class="relative inline-flex items-center">
				{{ $keyword->keyword }}
				<button
					type="button"
					x-on:click.stop.prevent="copyKeyword()"
					class="ml-2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
					title="Copy to clipboard"
				>
					<svg x-show="!keywordCopied" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M8 4v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.242a2 2 0 0 0-.602-1.43L16.083 2.57A2 2 0 0 0 14.685 2H10a2 2 0 0 0-2 2Z" />
						<path stroke-linecap="round" stroke-linejoin="round" d="M16 18v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2" />
					</svg>
					<svg x-show="keywordCopied" x-cloak class="w-4 h-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
					</svg>
				</button>
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
		<td class="px-6 pt-4 pb-1 w-full">
			<!-- Keyword Metrics -->
			<div class="flex flex-wrap">
				@foreach ($evaluation->metrics as $metric)
					<livewire:evaluations.evaluation-keyword-metric
							:keyword="$keyword"
							:metric="$metric"
							:baseline-value="$baselineValues[sprintf('%s_%d', $metric->scorer_type, $metric->num_results)] ?? null"
							key="evaluation-keyword-metric-{{ $keyword->id }}-{{ $metric->id }}"
					/>
				@endforeach
			</div>
		</td>
	</tr>
	<tr x-show="expand" x-cloak>
		<td colspan="3" class="p-0 m-0">
			<x-evaluations.keyword-expanded :evaluation="$evaluation" :keyword="$keyword" />
		</td>
	</tr>
</tbody>
