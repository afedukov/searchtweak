<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Endpoints') }}
		</h2>
	</x-slot>

	<div>
		<!-- Endpoints -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Total Endpoints -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $endpoints->total() }} {{ Str::plural('endpoint', $endpoints->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Filter button -->
							<div class="relative flex" x-data="{ open: false }">
								<button
										class="relative btn bg-white dark:bg-slate-800 border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600 text-slate-500 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300"
										aria-haspopup="true"
										@click.prevent="open = !open"
										:aria-expanded="open"
								>
									<span class="sr-only">Filter</span><wbr>
									<svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
										<path d="M9 15H7a1 1 0 010-2h2a1 1 0 010 2zM11 11H5a1 1 0 010-2h6a1 1 0 010 2zM13 7H3a1 1 0 010-2h10a1 1 0 010 2zM15 3H1a1 1 0 010-2h14a1 1 0 010 2z" />
									</svg>

									<div class="inline text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase ml-2">Filter by status</div>

									<!-- Filter applied badge -->
									@if (count($filterStatus) < count($filterStatuses))
										<x-block.filter-applied-badge />
									@endif
								</button>
								<div
										class="origin-top-right z-10 absolute top-full min-w-56 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 pt-1.5 rounded shadow-lg overflow-hidden mt-1 sm:left-auto sm:right-0"
										@click.outside="open = false"
										@keydown.escape.window="open = false"
										x-show="open"
										x-transition:enter="transition ease-out duration-200 transform"
										x-transition:enter-start="opacity-0 -translate-y-2"
										x-transition:enter-end="opacity-100 translate-y-0"
										x-transition:leave="transition ease-out duration-200"
										x-transition:leave-start="opacity-100"
										x-transition:leave-end="opacity-0"
										x-cloak
								>
									<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase pt-1.5 pb-2 px-3">Filter</div>
									<ul class="mb-4">
										@foreach ($filterStatuses as $item)
											<li class="py-1 px-3" wire:key="{{ $item['key'] }}">
												<label class="flex items-center">
													<input type="checkbox" class="form-checkbox" wire:model="filterStatus" value="{{ $item['key'] }}">
													<span class="text-sm font-medium ml-2">{{ $item['name'] }}</span>
												</label>
											</li>
										@endforeach
									</ul>
									<div class="py-2 px-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/20">
										<ul class="flex items-center justify-between">
											<li>
												<button wire:click="resetFilter" @click="open = false" class="btn-xs bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-500 dark:text-slate-300 hover:text-slate-600 dark:hover:text-slate-200">
													{{ __('Reset') }}
												</button>
											</li>
											<li>
												<button wire:click="$refresh" class="btn-xs bg-indigo-500 hover:bg-indigo-600 text-white" @click="open = false" @focusout="open = false">
													{{ __('Apply') }}
												</button>
											</li>
										</ul>
									</div>
								</div>
							</div>

							<!-- Create Endpoint button -->
							@if (Gate::check('create-endpoint', Auth::user()->currentTeam))
								<x-button wire:click="createEndpoint" wire:loading.attr="disabled" class="relative flex">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
										<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									<span class="ml-2">
										{{ __('Create Endpoint') }}
									</span>
								</x-button>
							@endif
						</div>

					</div>

				</header>
				<div class="p-3">
					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto" x-data="{ confirmingEndpointRemoval: @entangle('confirmingEndpointRemoval'), endpointIdBeingRemoved: @entangle('endpointIdBeingRemoved') }">
						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3 w-1/3 min-w-52">
									{{ __('Endpoint') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Method') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('URL') }}
								</th>
								<th scope="col" class="px-5 py-3 text-center">
									{{ __('Custom Headers') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Active') }}
								</th>
								@if (Gate::check('create-model', Auth::user()->currentTeam))
									<th scope="col" class="px-5 py-3 text-center">
										{{ __('Model') }}
									</th>
								@endif
								<th scope="col" class="px-5 py-3 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($endpoints as $endpoint)
								<tr wire:key="endpoint-item-{{ $endpoint->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-5 py-4 font-medium text-gray-900 dark:text-white align-baseline">
										<div>
											{{ $endpoint->name }}
										</div>
										<div class="text-sm text-gray-400 dark:text-gray-400">
											{{ $endpoint->description }}
										</div>
									</th>
									<td class="px-5 py-4 align-baseline">
										<span class="text-sm font-medium me-2 px-2.5 py-0.5 rounded {{ $endpoint->getMethodBadgeClass() }}">
											{{ $endpoint->method }}
										</span>
									</td>
									<td class="px-5 py-4 align-baseline">
										<p class="w-[100px] sm:w-[150px] md:w-[200px] lg:w-[300px] truncate">
											{{ $endpoint->url_shortened }}
										</p>
									</td>
									<td class="px-5 py-4 text-center align-baseline">
										@if ($endpoint->headers)
											<x-typography.check-mark />
										@else
											<span class="font-mono text-xs">&mdash;</span>
										@endif
									</td>
									<td class="px-5 py-4 align-baseline">
										<!-- Toggle Endpoint Active -->
										@if (Gate::check('toggle', $endpoint))
											<label class="inline-flex items-center cursor-pointer">
												<input wire:change="toggleEndpointActive('{{ $endpoint->id }}')" type="checkbox" value="1" class="sr-only peer" @checked($endpoint->isActive()) />
												<div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
											</label>
										@endif
									</td>
									@if (Gate::check('create-model', Auth::user()->currentTeam))
										<td class="px-5 py-4 text-center align-baseline">
											@if ($endpoint->isActive())
												<x-typography.round-button-plus size="small" wire:click="$toggle('editModelModal')" x-on:click="$wire.set('modelForm.endpoint_id', '{{ $endpoint->id }}')" />
											@else
												<span class="font-mono text-xs">&mdash;</span>
											@endif
										</td>
									@endif
									<td class="px-5 py-4 text-right align-baseline">
										<x-block.context-menu id="context-{{ $endpoint->id }}">
											<!-- Edit Endpoint -->
											@if (Gate::check('update', $endpoint))
												<x-block.context-menu-item wire:click="editEndpoint('{{ $endpoint->id }}')">
													{{ __('Edit') }}
												</x-block.context-menu-item>
											@endif

											<!-- Clone Endpoint -->
											@if (Gate::check('create-endpoint', Auth::user()->currentTeam))
												<x-block.context-menu-item wire:click="cloneEndpoint('{{ $endpoint->id }}')">
													{{ __('Clone') }}
												</x-block.context-menu-item>
											@endif

											<!-- Delete Endpoint -->
											@if (Gate::check('delete', $endpoint))
												<x-block.context-menu-item
														@click="
															endpointIdBeingRemoved = {{ $endpoint->id }};
															confirmingEndpointRemoval = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $endpoint->id }}').hide();
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
									<td class="px-5 py-4 text-center" colspan="7">
										<span class="text-gray-400 dark:text-gray-500">
											{{ __('No endpoints found') }}
										</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $endpoints->links() }}
						</nav>

						<!-- Alpine Modals -->

						<!-- Delete Endpoint Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="confirmingEndpointRemoval" x-cloak>
							<x-slot name="title">
								{{ __('Delete Endpoint') }}
							</x-slot>

							<x-slot name="content">
								{{ __('Are you sure you would like to delete this endpoint?') }}
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="confirmingEndpointRemoval = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-danger-button class="ms-3" wire:click="deleteEndpoint" wire:loading.attr="disabled">
									{{ __('Delete') }}
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>
					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Create/Edit Endpoint Modal -->
	<x-modals.endpoint-edit :create="$endpointForm->endpoint === null" />

	<!-- Create Model Modal -->
	<x-modals.model-edit create fixed :endpoints="$modelFormEndpoints" :execution-result="$executionResult" />

</div>
