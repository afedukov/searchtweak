<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<div class="flex items-center gap-2">
				Evaluation: <strong>{{ $evaluation->name }}</strong>

				<div class="mb-1">
					<!-- Evaluation Scale Type -->
					<livewire:evaluations.evaluation-scale-type :evaluation="$evaluation" key="evaluation-scale-type-{{ $evaluation->id }}" />
				</div>
			</div>
		</h2>
	</x-slot>

	<div>
		<!-- Navigation Tabs -->
		<x-block.navigation-tabs>

			<!-- Go Back -->
			<x-go-back href="{{ route('evaluations') }}" />

			<!-- User Feedback -->
			<x-block.navigation-tab>
				<a href="{{ route('evaluation.feedback', $evaluation->id) }}" class="font-medium text-sm text-blue-600 dark:text-blue-500 hover:underline">
					User Feedback
				</a>
			</x-block.navigation-tab>

			<!-- Give Feedback -->
			@if ($evaluation->isActive())
				<x-block.navigation-tab>
					<a href="{{ route('feedback', $evaluation->id) }}" class="font-medium text-sm text-blue-600 dark:text-blue-500 hover:underline">
						Give Feedback
					</a>
				</x-block.navigation-tab>
			@endif

		</x-block.navigation-tabs>

		<!-- Evaluation -->
		<div
				class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto"
				x-data="{ excludedMetrics: $persist([]).as('excluded-metrics-@js($evaluation->id)') }"
		>

			<!-- Evaluation Widgets -->
			<div class="grid grid-cols-12 gap-6 mb-8">

				<!-- Metrics -->
				@foreach ($evaluation->metrics as $metric)
					<livewire:evaluations.metric-card :metric="$metric" :keywords-count="$keywords->total()" key="metric-card-{{ $metric->id }}" />
				@endforeach

			</div>

			<!-- Evaluation Keywords -->
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Total Keywords -->
							<span class="font-semibold text-gray-400 dark:text-gray-400 whitespace-nowrap">
								{{ $keywords->total() }} {{ Str::plural('keyword', $keywords->total()) }}
							</span>

							<!-- Archived Badge -->
							<livewire:evaluations.evaluation-archived-badge :evaluation="$evaluation" key="evaluation-archived-badge-{{ $evaluation->id }}" />

							<!-- Evaluation Status -->
							<livewire:evaluations.evaluation-status :evaluation="$evaluation" key="evaluation-status-{{ $evaluation->id }}" />

							<!-- Evaluation Add To Dashboard Button -->
							<div class="relative inline">
								<button
										data-popover-target="attach-evaluation-{{ $evaluation->id }}"
										@class([
											'rounded-full disabled:opacity-50 -mt-0.5',
											'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300' => $attached,
											'text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300' => !$attached,
										])
										wire:click="attach"
										wire:loading.attr="disabled"
								>
									<div class="h-7 w-7 p-1">
										<i class="fa-solid fa-thumbtack"></i>
									</div>
								</button>

								<x-tooltip id="attach-evaluation-{{ $evaluation->id }}" with-arrow>
									<span class="whitespace-nowrap">
										@if ($attached)
											Remove from Dashboard
										@else
											Add to Dashboard
										@endif
									</span>
								</x-tooltip>
							</div>

							<!-- Edit Icon -->
							@if ($evaluation->isPending() && Gate::check('update', $evaluation))
								<a href="javascript:void(0)" wire:click="editEvaluation('{{ $evaluation->id }}')" class="flex items-center justify-center h-6 w-6 text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
									<i class="fa-regular fa-pen-to-square"></i>
								</a>
							@endif

							<!-- Evaluation Tags -->
							<x-tags.tags-list :tags="$evaluation->tags" empty-label="" />
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-3">

							@if ($evaluation->isFinished() && Gate::check('export', $evaluation))
								<!-- Export Button -->
								<button data-popover-target="export-judgements" type="button" wire:click.prevent="exportEvaluation('{{ $evaluation->id }}')" wire:loading.attr="disabled" class="flex items-center justify-center flex-shrink-0 px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-primary-700 focus:z-10 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
									<svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
										<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
									</svg>
									Export
								</button>
								<x-tooltip id="export-judgements" with-arrow>
									<span class="whitespace-nowrap">
										Export judgements as CSV
									</span>
								</x-tooltip>
							@endif

							<!-- Finish Evaluation Button -->
							<livewire:evaluations.evaluation-finish-button :evaluation="$evaluation" key="evaluation-finish-button-{{ $evaluation->id }}" />

							<div class="flex gap-3">
								<!-- Progress Badge -->
								<livewire:evaluations.evaluation-progress total :evaluation="$evaluation" class="min-w-32 sm:min-w-44" key="evaluation-progress-{{ $evaluation->id }}" />

								<!-- Control Evaluation -->
								<livewire:evaluations.evaluation-control :evaluation="$evaluation" key="evaluation-control-{{ $evaluation->id }}" />
							</div>
						</div>

					</div>

				</header>

				<!-- Second Header -->
				<div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Search Box -->
							<livewire:components.search-box wire:model.live="query" placeholder="Search for keywords" key="evaluation-search-box" />

							<!-- Keywords Order By -->
							<livewire:evaluations.keywords-order-by :evaluation="$evaluation" wire:model.live="orderBy" key="evaluation-keywords-order" />

							<!-- Keywords Select Metrics -->
							<livewire:evaluations.keywords-select-metrics :evaluation="$evaluation" key="evaluation-keywords-select-metrics" />
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-3 items-center">
							<!-- Keywords Count -->
							<livewire:evaluations.evaluation-keywords-count :evaluation="$evaluation" key="evaluation-keywords-count-{{ $evaluation->id }}" />
						</div>

					</div>

				</div>

				<div class="p-3">

					<!-- Baseline Evaluation -->
					<livewire:evaluations.baseline-evaluation :baseline="$baseline" key="baseline-evaluation-{{ $baseline?->id }}" />

					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto">
						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
								<tr>
									<th scope="col" class="px-6 py-3 w-16">
									</th>
									<th scope="col" class="px-6 py-3">
										{{ __('Keyword') }}
									</th>
									<th scope="col" class="px-6 py-3">
										{{ __('Metrics') }}
									</th>
								</tr>
							</thead>
							@forelse ($keywords as $keyword)
								<livewire:evaluations.evaluation-keyword-row
										:evaluation="$evaluation"
										:keyword="$keyword"
										:baseline-values="$baselineValues[$keyword->id] ?? []"
										key="evaluation-keyword-row-{{ $keyword->id }}-{{ $evaluation->status }}-{{ $baseline?->id }}"
								/>
							@empty
								<tr>
									<td colspan="3" class="px-5 py-4 text-center">
										<span class="text-gray-500 dark:text-gray-400">No keywords found.</span>
									</td>
								</tr>
							@endforelse
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $keywords->links() }}
						</nav>
					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Edit Evaluation Modal -->
	<x-modals.evaluation-edit fixed :models="[$evaluation->model]" />

</div>
