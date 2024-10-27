<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Models') }}
		</h2>
	</x-slot>

	<div>
		<!-- Models -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Total Models -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $models->total() }} {{ Str::plural('model', $models->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Filter button -->

							<!-- Tags Filter -->
							<livewire:tags.filter-tags :tags="Auth::user()->currentTeam->tags" wire:model.live="filterTagId" wire:key="{{ md5(mt_rand()) }}" />

							<!-- Create Model button -->
							@if (Gate::check('create-model', Auth::user()->currentTeam))
								<x-button wire:click="createModel" @click.prevent="$wire.set('executionResult', null)" wire:loading.attr="disabled" class="relative flex">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
										<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									<span class="ml-2">
										{{ __('Create Model') }}
									</span>
								</x-button>
							@endif
						</div>

					</div>

				</header>
				<div class="p-3">
					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto" x-data="{ confirmingModelRemoval: @entangle('confirmingModelRemoval'), modelIdBeingRemoved: @entangle('modelIdBeingRemoved') }">
						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-4 py-3 min-w-52">
									{{ __('Model') }}
								</th>
								<th scope="col" class="px-4 py-3 min-w-64">
									{{ __('Endpoint') }}
								</th>
								<th scope="col" class="px-4 py-3 w-full align-baseline flex">
									{{ __('Metrics') }}
									<div class="inline-block ml-1">
										<x-tooltip-info>
											<div class="max-w-80 font-light normal-case">
												Latest metrics of each scorer type for the model.
											</div>
										</x-tooltip-info>
									</div>
								</th>
								<th scope="col" class="px-4 py-3 text-center">
									{{ __('Evaluation') }}
								</th>
								<th scope="col" class="px-4 py-3 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($models as $model)
								<tr wire:key="model-item-{{ $model->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-4 py-4 font-medium text-gray-900 dark:text-white align-baseline">
										<div class="max-w-64 min-w-28">
											<a href="{{ route('model', $model->id) }}">
												<div>
													{{ $model->name }}
												</div>
												<div class="text-sm text-gray-400 dark:text-gray-400 align-baseline">
													{{ $model->description }}
												</div>
											</a>
										</div>

										<x-tags.tags-list :tags="$model->tags" empty-label="" class="mt-1" />
									</th>
									<td class="px-4 py-4 align-baseline">
										<x-endpoints.endpoint-badge :endpoint="$model->endpoint" />
									</td>
									<td class="px-4 py-4 align-baseline">
										<!-- Model Metrics -->
										<div class="flex flex-wrap gap-3">
											@forelse ($model->getMetrics() as $modelMetric)
												<x-metrics.evaluation-metric :metric="$modelMetric->getLastMetric()" :keywords-count="$modelMetric->getKeywordsCount()" />
											@empty
												<span class="text-xs text-gray-400 dark:text-gray-500">
													{{ __('No metrics') }}
												</span>
											@endforelse
										</div>
									</td>
									<td class="px-4 py-4 text-center align-baseline">
										@if ($model->canCreateEvaluations() && Gate::check('create-evaluation', Auth::user()->currentTeam))
											<x-typography.round-button-plus
													size="small"
													wire:click="$toggle('editEvaluationModal')"
													@click="
														$wire.set('evaluationForm.model_id', '{{ $model->id }}');
														$wire.set('evaluationForm.keywords', JSON.parse('{{ json_encode($model->getKeywords()) }}').join('\n'));
														$wire.set('evaluationForm.tags', JSON.parse('{{ json_encode($model->tags) }}'));
													"
											/>
										@else
											<span class="font-mono text-xs">&mdash;</span>
										@endif
									</td>
									<td class="px-4 py-4 text-right align-baseline">
										<!-- Context Menu -->
										<x-block.context-menu id="context-{{ $model->id }}">
											<!-- Edit Model -->
											@if (Gate::check('update', $model))
												<x-block.context-menu-item wire:click="editModel('{{ $model->id }}')">
													{{ __('Edit') }}
												</x-block.context-menu-item>
											@endif

											<!-- Clone Model -->
											@if (Gate::check('create-model', Auth::user()->currentTeam))
												<x-block.context-menu-item wire:click="cloneModel('{{ $model->id }}')">
													{{ __('Clone') }}
												</x-block.context-menu-item>
											@endif

											<!-- Delete Model -->
											@if (Gate::check('delete', $model))
												<x-block.context-menu-item
														@click="
															modelIdBeingRemoved = {{ $model->id }};
															confirmingModelRemoval = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $model->id }}').hide();
														"
														class="text-rose-500"
												>
													{{ __('Delete') }}
												</x-block.context-menu-item>
											@endif
										</x-block.context-menu>
									</td>
								</tr>
							@empty
								<tr>
									<td class="px-5 py-4 text-center" colspan="8">
										<span class="text-gray-400 dark:text-gray-500">
											{{ __('No models found') }}
										</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $models->links() }}
						</nav>

						<!-- Alpine Modals -->

						<!-- Delete Model Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="confirmingModelRemoval" x-cloak>
							<x-slot name="title">
								{{ __('Delete Model') }}
							</x-slot>

							<x-slot name="content">
								{{ __('Are you sure you would like to delete this model?') }}
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="confirmingModelRemoval = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-danger-button class="ms-3" wire:click="deleteModel" wire:loading.attr="disabled">
									{{ __('Delete') }}
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Create/Edit Model Modal -->
	<x-modals.model-edit :create="$modelForm->model === null" :fixed="$modelForm->model !== null" :endpoints="$modelFormEndpoints" :execution-result="$executionResult" />

	<!-- Create Evaluation Modal -->
	<x-modals.evaluation-edit create fixed :models="$allModels" />

	<!-- Edit Endpoint Modal -->
	<x-modals.endpoint-edit />

</div>
