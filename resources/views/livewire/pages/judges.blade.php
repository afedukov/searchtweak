<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<span class="uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white ml-2">AI</span>
			{{ __('Judges') }}
		</h2>
	</x-slot>

	<div>
		<!-- Judges -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Total Judges -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $judges->total() }} {{ Str::plural('judge', $judges->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Status Filter -->
							<div class="inline-flex rounded-md items-center" role="group">
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterStatusMode" wire:loading.attr="disabled" name="judges-filter-status" id="judges-filter-status-all" value="all" class="hidden peer" />
									<label for="judges-filter-status-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('All') }}
									</label>
								</div>
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterStatusMode" wire:loading.attr="disabled" name="judges-filter-status" id="judges-filter-status-active" value="active" class="hidden peer" />
									<label for="judges-filter-status-active" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('Active') }}
									</label>
								</div>
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterStatusMode" wire:loading.attr="disabled" name="judges-filter-status" id="judges-filter-status-archived" value="archived" class="hidden peer" />
									<label for="judges-filter-status-archived" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('Archived') }}
									</label>
								</div>
							</div>

							<!-- View Logs button -->
							@if (Gate::check('view-judges'))
								<a href="{{ route('judge-logs') }}"
								   class="btn bg-white dark:bg-slate-800 border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600 text-slate-500 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
										<path d="M14 2H2C1.4 2 1 2.4 1 3v10c0 .6.4 1 1 1h12c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1zm-1 9H3V7h10v4zM3 6V4h10v2H3z"/>
									</svg>
									<span class="ml-2">{{ __('View Logs') }}</span>
								</a>
							@endif

							<!-- Create Judge button -->
							@if (Gate::check('create-judge', Auth::user()->currentTeam))
								<x-button wire:click="createJudge" wire:loading.attr="disabled" class="relative flex">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
										<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									<span class="ml-2">
										{{ __('Create Judge') }}
									</span>
								</x-button>
							@endif
						</div>

					</div>

				</header>
				<div class="p-3">
					<!-- Table and Filters -->
					<div
							class="sm:rounded-lg overflow-x-auto"
							x-data="{
								confirmingJudgeRemoval: $wire.entangle('confirmingJudgeRemoval'),
								judgeIdBeingRemoved: $wire.entangle('judgeIdBeingRemoved'),
							}"
					>
						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3 w-1/3 min-w-52">
									{{ __('Name / Description') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Provider') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Model') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Tags') }}
								</th>
								<th scope="col" class="px-5 py-3 text-center">
									{{ __('Pairs Judged') }}
								</th>
								<th scope="col" class="px-5 py-3 text-center">
									{{ __('Batch Size') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Active') }}
								</th>
								<th scope="col" class="px-5 py-3 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($judges as $judge)
								<tr wire:key="judge-item-{{ $judge->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-5 py-4 font-medium text-gray-900 dark:text-white align-baseline">
										<div class="flex items-center gap-2">
											<x-block.judge-name
												:judge="$judge"
												icon-size="sm"
												name-class="text-base font-medium text-gray-900 dark:text-white"
											/>
											<livewire:judges.judge-status-badge :judge-id="$judge->id" :team-id="Auth::user()->current_team_id" :key="'judge-status-'.$judge->id" />
										</div>
										<div class="ml-6 text-sm text-gray-400 dark:text-gray-400">
											{{ $judge->description }}
										</div>
									</th>
									<td class="px-5 py-4 align-baseline">
										<span class="text-sm font-medium me-2 px-2.5 py-1.5 rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 whitespace-nowrap">
											{{ \App\Models\Judge::getProviderLabel($judge->provider) }}
										</span>
									</td>
									<td class="px-5 py-4 align-baseline">
										<span class="font-mono text-sm">{{ $judge->model_name }}</span>
									</td>
									<td class="px-5 py-4 align-baseline">
										<x-tags.tags-list :tags="$judge->tags" empty-label="" />
									</td>
									<td class="px-5 py-4 text-center align-baseline">
										<livewire:judges.judge-pairs-judged-count :judge-id="$judge->id" :team-id="Auth::user()->current_team_id" :key="'judge-pairs-judged-'.$judge->id" />
									</td>
									<td class="px-5 py-4 text-center align-baseline">
										<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">{{ $judge->getBatchSize() }}</span>
									</td>
									<td class="px-5 py-4 align-baseline">
										<!-- Toggle Judge Active -->
										@if (Gate::check('toggle', $judge))
											<label class="inline-flex items-center cursor-pointer">
												<input wire:change="toggleJudgeActive('{{ $judge->id }}')" type="checkbox" value="1" class="sr-only peer" @checked($judge->isActive()) />
												<div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
											</label>
										@endif
									</td>
									<td class="px-5 py-4 text-right align-baseline">
										<x-block.context-menu id="context-{{ $judge->id }}">
											<!-- Edit Judge -->
											@if (Gate::check('update', $judge))
												<x-block.context-menu-item wire:click="editJudge('{{ $judge->id }}')">
													{{ __('Edit') }}
												</x-block.context-menu-item>
											@endif

											<!-- Clone Judge -->
											@if (Gate::check('create-judge', Auth::user()->currentTeam))
												<x-block.context-menu-item wire:click="cloneJudge('{{ $judge->id }}')">
													{{ __('Clone') }}
												</x-block.context-menu-item>
											@endif

											<!-- View Logs -->
											@if (Gate::check('view-judges'))
												<x-block.context-menu-item :href="route('judge.logs', $judge->id)">
													{{ __('View Logs') }}
												</x-block.context-menu-item>
											@endif

											<!-- Delete Judge -->
											@if (Gate::check('delete', $judge))
												<x-block.context-menu-item
														@click="
															judgeIdBeingRemoved = {{ $judge->id }};
															confirmingJudgeRemoval = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $judge->id }}').hide();
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
											{{ __('No judges found') }}
										</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $judges->links() }}
						</nav>

						<!-- Alpine Modals -->

						<!-- Delete Judge Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="confirmingJudgeRemoval" x-cloak>
							<x-slot name="title">
								{{ __('Delete Judge') }}
							</x-slot>

							<x-slot name="content">
								{{ __('Are you sure you would like to delete this judge?') }}
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="confirmingJudgeRemoval = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-danger-button class="ms-3" wire:click="deleteJudge" wire:loading.attr="disabled">
									{{ __('Delete') }}
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>
					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Create/Edit Judge Modal -->
	<x-modals.judge-edit :create="$this->judgeForm->judge === null" />

</div>
