<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Teams') }}
		</h2>
	</x-slot>

	<div>
		<!-- Owned Teams -->
		<div class="px-4 sm:px-5 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<h2 class="font-bold text-slate-800 dark:text-slate-100">
								{{ __('Owned Teams') }}
							</h2>
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $ownedTeams->total() }} {{ Str::plural('team', $ownedTeams->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Create Team button -->
							<x-button wire:click="$toggle('createTeamModal')" wire:loading.attr="disabled" wire:target="photo">
								<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
									<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
								</svg>
								<span class="ml-2">
									{{ __('Create Team') }}
								</span>
							</x-button>
						</div>

					</div>

				</header>
				<div class="p-3">
					<!-- Table -->
					<div class="sm:rounded-lg overflow-x-auto">
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3">
									{{ __('Team name') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Role') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Members') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Active') }}
								</th>
								<th scope="col" class="px-5 py-3 w-36 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@foreach ($ownedTeams as $team)
								<tr wire:key="owned-{{ $team->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-5 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
										{{ $team->name }}
									</th>
									<td class="px-5 py-4">
										<x-block.user-role :team="$team" />
									</td>
									<td class="px-5 py-4">
										{{ $team->users_count + 1 }}
									</td>
									<td class="px-5 py-4">
										@if ($team->id === Auth::user()->currentTeam->id)
											<svg class="mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
										@else
											<a href="javascript:void(0)" wire:click="switchTeam({{ $team->id }}, 'teams.all')" class="font-medium text-blue-600 dark:text-blue-500 hover:underline mr-3">
												{{ __('Switch') }}
											</a>
										@endif
									</td>
									<td class="px-5 py-4 text-right"></td>
								</tr>
							@endforeach
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $ownedTeams->links() }}
						</nav>
					</div>

				</div>
			</div>
		</div>

		<!-- Shared Teams -->
		<div class="px-4 sm:px-5 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<h2 class="font-bold text-slate-800 dark:text-slate-100">
								{{ __('Shared Teams') }}
							</h2>
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $teams->total() }} {{ Str::plural('team', $teams->total()) }}
							</span>
						</div>

					</div>

				</header>
				<div class="p-3">
					<!-- Table -->
					<div
							class="sm:rounded-lg overflow-x-auto"
							x-data="{
								confirmingLeavingTeam: $wire.entangle('confirmingLeavingTeam'),
								teamId: $wire.entangle('teamId'),
							}"
					>

						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3">
									{{ __('Team name') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Team owner') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Role') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Members') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Active') }}
								</th>
								<th scope="col" class="px-5 py-3 w-36 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@foreach ($teams as $team)
								<tr wire:key="shared-{{ $team->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-5 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
										{{ $team->name }}
									</th>
									<td class="px-5 py-4">
										<x-block.user-name :user="$team->owner" />
									</td>
									<td class="px-5 py-4">
										<x-block.user-role :team="$team" />
									</td>
									<td class="px-5 py-4">
										{{ $team->users_count + 1 }}
									</td>
									<td class="px-5 py-4">
										@if ($team->id === Auth::user()->currentTeam->id)
											<svg class="mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
										@else
											<a href="javascript:void(0)" wire:click="switchTeam({{ $team->id }}, 'teams.all')" class="font-medium text-blue-600 dark:text-blue-500 hover:underline mr-3">
												{{ __('Switch') }}
											</a>
										@endif
									</td>
									<td class="px-5 py-4 text-right">
										<x-block.context-menu id="context-{{ $team->id }}">
											<!-- Leave Team -->
											<x-block.context-menu-item
													@click="
															teamId = {{ $team->id }};
															confirmingLeavingTeam = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $team->id }}').hide();
														"
													class="text-rose-500"
											>
												{{ __('Leave') }}
											</x-block.context-menu-item>
										</x-block.context-menu>
									</td>
								</tr>
							@endforeach
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $teams->links() }}
						</nav>

						<!-- Alpine Modals -->

						<!-- Leave Team Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="confirmingLeavingTeam" x-cloak>
							<x-slot name="title">
								{{ __('Leave Team') }}
							</x-slot>

							<x-slot name="content">
								{{ __('Are you sure you would like to leave this team?') }}
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="confirmingLeavingTeam = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-danger-button class="ms-3" wire:click="leaveTeam" wire:loading.attr="disabled">
									{{ __('Leave Team') }}
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Create Team Modal -->
	<x-dialog-modal wire:model.live="createTeamModal">
		<x-slot name="title">
			{{ __('Create Team') }}
		</x-slot>

		<x-slot name="content">
			<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">
				<x-form.label.label-required for="name" value="{{ __('Team Name') }}" />
				<x-input id="name" type="text" wire:model="state.name" autofocus />
				<x-input-error for="name" />
				<p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					{{ __('Create a new team to collaborate with others on projects. You can invite others to your team once you create it.') }}
				</p>
			</div>
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('createTeamModal')" wire:loading.attr="disabled">
				{{ __('Cancel') }}
			</x-secondary-button>

			<x-button class="ms-3" wire:click="createTeam" wire:loading.attr="disabled">
				{{ __('Create Team') }}
			</x-button>
		</x-slot>
	</x-dialog-modal>
</div>
