<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Evaluations') }}
		</h2>
	</x-slot>

	<div>
		<!-- Evaluations -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Total Evaluations -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $evaluations->total() }} {{ Str::plural('evaluation', $evaluations->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">

							<!-- Status Filter -->
							<livewire:evaluations.filter-status wire:model="filterStatus" wire:key="{{ md5(mt_rand()) }}" />

							<!-- Tags Filter -->
							<livewire:tags.filter-tags :tags="Auth::user()->currentTeam->tags" wire:model.live="filterTagId" wire:key="{{ md5(mt_rand()) }}" />

							<!-- Create Evaluation button -->
							@if (Gate::check('create-evaluation', Auth::user()->currentTeam))
								<x-button wire:click="createEvaluation" wire:loading.attr="disabled" class="relative flex">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
										<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									<span class="ml-2">
										{{ __('Create Evaluation') }}
									</span>
								</x-button>
							@endif
						</div>

					</div>

				</header>
				<div class="p-3">
					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto">
						<x-evaluations.evaluations-table :evaluations="$evaluations" :evaluation-form="$evaluationForm" with-model />
					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Modals -->

	<!-- Edit Evaluation Modal -->
	<x-modals.evaluation-edit :create="$evaluationForm->evaluation === null" :fixed="$evaluationForm->evaluation !== null" :models="$allModels" />

</div>
