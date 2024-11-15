@php
	$statusLabels = \App\Models\SearchEvaluation::STATUS_LABELS;
@endphp

<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<div class="flex items-center gap-2">
				Model: <strong>{{ $model->name }}</strong>
			</div>
		</h2>
	</x-slot>

	<div>
		<!-- Navigation Tabs -->
		<x-block.navigation-tabs>

			<!-- Go Back -->
			<x-go-back href="{{ route('models') }}"/>

		</x-block.navigation-tabs>

		<!-- Model -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

			<!-- Model Widgets -->
			<div class="grid grid-cols-12 gap-6 mb-8">
				<livewire:models.model-metrics-card :model="$model" key="model-metrics-card-{{ $model->id }}" />
			</div>

			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<!-- First Header -->
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">

							<!-- Total Evaluations -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $evaluations->total() }} {{ Str::plural('evaluation', $evaluations->total()) }}
							</span>

							<!-- Edit Icon -->
							@if (Gate::check('update', $model))
								<a href="javascript:void(0)" wire:click="editModel('{{ $model->id }}')" class="flex items-center justify-center h-6 w-6 text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
									<i class="fa-regular fa-pen-to-square"></i>
								</a>
							@endif

							<!-- Model Tags -->
							<x-tags.tags-list :tags="$model->tags" empty-label="" />
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">

							<!-- Create Evaluation button -->
							@if ($model->canCreateEvaluations() && Gate::check('create-evaluation', Auth::user()->currentTeam))
								<x-button
										wire:loading.attr="disabled"
										class="relative flex"
										wire:click.prevent="$toggle('editEvaluationModal')"
										@click="
											$wire.set('evaluationForm.model_id', '{{ $model->id }}');
											$wire.set('evaluationForm.keywords', {{ Js::from($model->getKeywordsString()) }});
											$wire.set('evaluationForm.tags', {{ Js::from($model->tags) }});
											$wire.set('evaluationForm.transformers', {{ Js::from(\App\Services\Transformers\Transformers::getDefaultFormTransformers()) }});
										"
								>
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
										<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z"/>
									</svg>
									<span class="ml-2">
										{{ __('Create Evaluation') }}
									</span>
								</x-button>
							@endif
						</div>

					</div>

				</header>

				<!-- Second Header -->
				<div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Search -->
							<div class="relative">
								<div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
									<svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
										<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
									</svg>
								</div>
								<input wire:model.live.debounce.500ms="query" type="text" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-md w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search for evaluations" />
							</div>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Archived Filter -->
							<livewire:evaluations.filter-archived wire:model.live="filterArchived" key="evaluations-filter-archived" />

							<!-- Status Filter -->
							<livewire:evaluations.filter-status wire:model="filterStatus" key="evaluations-filter-status" />

							<!-- Tags Filter -->
							<livewire:tags.filter-tags :tags="Auth::user()->currentTeam->tags" wire:model.live="filterTagId" key="evaluations-filter-tags" />
						</div>

					</div>

				</div>

				<div class="p-3">
					@if ($baseline)
						<!-- Baseline Evaluation -->
						<x-evaluations.baseline-evaluation :evaluation="$baseline" />
					@endif

					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto">
						<x-evaluations.evaluations-table :evaluations="$evaluations" :evaluation-form="$evaluationForm" />
					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Modals -->

	<!-- Edit Evaluation Modal -->
	<x-modals.evaluation-edit create fixed :models="$allModels" />

	<!-- Edit Model Modal -->
	<x-modals.model-edit fixed :endpoints="$modelFormEndpoints" :execution-result="$executionResult" />

</div>
