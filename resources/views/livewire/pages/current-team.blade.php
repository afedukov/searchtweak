<div x-data="{ showDeleteApiToken: false }">
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Current Team') }}
		</h2>
	</x-slot>

	<div>
		<!-- Users -->
		<div class="px-4 sm:px-5 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3 items-center">
							<h2 class="font-bold text-slate-800 dark:text-slate-100">
								{{ $team->name }}
							</h2>
							<!-- Total Users -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $users->total() }} {{ Str::plural('member', $users->total()) }}
							</span>
							<!-- Edit Icon -->
							@if (Gate::check('update', $team))
								<a href="#" data-popover-target="edit-team" wire:click.prevent="$toggle('editTeamModal')" class="flex items-center justify-center h-6 w-6 text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
									<i class="fa-regular fa-pen-to-square"></i>
								</a>
								<x-tooltip id="edit-team" with-arrow>
									<span class="whitespace-nowrap">
										Edit Team
									</span>
								</x-tooltip>
							@endif

							<!-- Api Icon -->
							@if (Gate::check('apiToken', $team))
								<a
										href="#"
										data-popover-target="manage-api-token"
										wire:click.prevent="$toggle('apiTokenModal')"
										@class([
											'flex items-center justify-center',
											'text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300' => !$apiToken,
											'text-green-500 dark:text-green-400 hover:text-green-600 dark:hover:text-green-300' => $apiToken,
										])
								>
									<svg class="w-9 h-9" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
										<rect width="24" height="24" fill="none"/>
										<path d="M20,6H4A2,2,0,0,0,2,8v8a2,2,0,0,0,2,2H20a2,2,0,0,0,2-2V8A2,2,0,0,0,20,6ZM9.29,14.8,9,13.73H7.16L6.87,14.8H5.17L7,9.07H9.09L11,14.8Zm6.34-3.14a1.7,1.7,0,0,1-.36.64,1.82,1.82,0,0,1-.67.44,2.75,2.75,0,0,1-1,.17h-.44V14.8H11.6V9.09h2a2.43,2.43,0,0,1,1.62.47,1.67,1.67,0,0,1,.55,1.35A2.36,2.36,0,0,1,15.63,11.66Zm2.58,3.14H16.66V9.09h1.55ZM8.45,11.53l.24.93H7.48l.24-.93c0-.13.08-.28.12-.47s.09-.38.13-.57a4.63,4.63,0,0,0,.1-.48c0,.13.07.29.11.5l.15.58Zm5.59-1a.57.57,0,0,1,.16.43.75.75,0,0,1-.11.42.59.59,0,0,1-.27.22.9.9,0,0,1-.37.07h-.31V10.34h.4A.63.63,0,0,1,14,10.51Z" fill-rule="evenodd"/>
									</svg>
								</a>
								<x-tooltip id="manage-api-token" with-arrow>
									<span class="whitespace-nowrap">
										Api Token
									</span>
								</x-tooltip>

							@endif
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Filter by role button -->
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

									<div class="inline text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase ml-2">Filter by role</div>

									<!-- Filter applied badge -->
									@if (count($filterRole) < count($filterRoles))
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
										@foreach ($filterRoles as $item)
											<li class="py-1 px-3" wire:key="$item['key']">
												<label class="flex items-center">
													<input type="checkbox" class="form-checkbox" wire:model="filterRole" value="{{ $item['key'] }}">
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

							<!-- Tags Filter -->
							<livewire:tags.filter-tags :tags="Auth::user()->currentTeam->tags" wire:model.live="filterTagId" key="team-filter-tags" />

							<!-- Send Message button -->
							@if (Gate::check('sendMessage', $team))
								<x-button wire:click="$toggle('sendTeamMessageModal')" wire:loading.attr="disabled" class="relative flex">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 18">
										<path d="M18 4H16V9C16 10.0609 15.5786 11.0783 14.8284 11.8284C14.0783 12.5786 13.0609 13 12 13H9L6.846 14.615C7.17993 14.8628 7.58418 14.9977 8 15H11.667L15.4 17.8C15.5731 17.9298 15.7836 18 16 18C16.2652 18 16.5196 17.8946 16.7071 17.7071C16.8946 17.5196 17 17.2652 17 17V15H18C18.5304 15 19.0391 14.7893 19.4142 14.4142C19.7893 14.0391 20 13.5304 20 13V6C20 5.46957 19.7893 4.96086 19.4142 4.58579C19.0391 4.21071 18.5304 4 18 4Z" fill="currentColor"/>
										<path d="M12 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V9C0 9.53043 0.210714 10.0391 0.585786 10.4142C0.960859 10.7893 1.46957 11 2 11H3V13C3 13.1857 3.05171 13.3678 3.14935 13.5257C3.24698 13.6837 3.38668 13.8114 3.55279 13.8944C3.71889 13.9775 3.90484 14.0126 4.08981 13.996C4.27477 13.9793 4.45143 13.9114 4.6 13.8L8.333 11H12C12.5304 11 13.0391 10.7893 13.4142 10.4142C13.7893 10.0391 14 9.53043 14 9V2C14 1.46957 13.7893 0.960859 13.4142 0.585786C13.0391 0.210714 12.5304 0 12 0Z" fill="currentColor"/>
									</svg>
									<span class="ml-2">
										{{ __('Send Message') }}
									</span>
								</x-button>
							@endif

							<!-- Invite User button -->
							@if (Gate::check('addTeamMember', $team))
								<x-button wire:click="$toggle('addTeamMemberModal')" wire:loading.attr="disabled" class="relative flex">
									<svg class="w-4 h-4 fill-current opacity-50 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 18">
										<path d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-2V5a1 1 0 0 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 0 0 2 0V9h2a1 1 0 1 0 0-2Z"/>
									</svg>
									<span class="ml-2">
										{{ __('Add User') }}
									</span>
									@if ($team->teamInvitations->count() > 0)
										<x-block.notification-badge :count="$team->teamInvitations->count()" class="w-6 h-6" />
									@endif
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
								showUserTags: false,
								tags: $wire.entangle('tags'),
								teamTags: $wire.entangle('teamTags'),
								availableTags: [],
								tag: $wire.entangle('tag'),
								showDeleteTag: $wire.entangle('showDeleteTag'),
								tagToDelete: $wire.entangle('tagToDelete'),
								confirmingTeamMemberRemoval: $wire.entangle('confirmingTeamMemberRemoval'),
								confirmingLeavingTeam: $wire.entangle('confirmingLeavingTeam'),
								teamMemberIdBeingRemoved: $wire.entangle('teamMemberIdBeingRemoved'),
							}"
							x-init="
								$watch('tag', value => { teamTags.push(value); });
								$watch('teamTags', value => { availableTags = teamTags.filter(t => !tags.find(tag => tag.id === t.id)); });
							"
					>
						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3">
									{{ __('User name') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('User email') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Role') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Tags') }}
								</th>
								<th scope="col" class="px-5 py-3 w-36 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($users as $user)
								<tr wire:key="user-{{ $user->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-5 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white align-baseline">
										<div class="inline-flex justify-center items-center">
											<x-block.user-name :user="$user" />
										</div>
									</th>
									<td class="px-5 py-4 align-baseline">
										<span class="text-gray-500 dark:text-gray-400">
											{{ $user->email }}
										</span>
									</td>
									<td class="px-5 py-4 align-baseline">
										<x-block.user-role
												:user="$user"
												:team="$team"
												:manage="Auth::user()->id !== $user->id && Gate::check('updateTeamMember', $team)"
										/>
									</td>
									<td class="px-5 py-4">
										<x-tags.tags-list :entity-id="$user->id" :tags="$user->getTeamTags($team->id)" :can-manage="Gate::check('manageUserTags', $team)" />
									</td>
									<td class="px-5 py-4 text-right align-baseline">
										<x-block.context-menu id="context-{{ $user->id }}">
											<!-- Send Message -->
											@if (Auth::user()->id !== $user->id && Gate::check('sendMessage', $team))
												<x-block.context-menu-item wire:click="sendMessageToUser('{{ $user->id }}')">
													{{ __('Send message') }}
												</x-block.context-menu-item>
											@endif

											<!-- Manage Role -->
											@if (Auth::user()->id !== $user->id && Gate::check('updateTeamMember', $team))
												<x-block.context-menu-item wire:click="manageRole('{{ $user->id }}')">
													{{ __('Manage role') }}
												</x-block.context-menu-item>
											@endif

											<!-- Remove Team Member -->
											@if (Auth::user()->id !== $user->id && Gate::check('removeTeamMember', $team))
												<x-block.context-menu-item
														@click="
															teamMemberIdBeingRemoved = {{ $user->id }};
															confirmingTeamMemberRemoval = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $user->id }}').hide();
														"
														class="text-rose-500"
												>
													{{ __('Remove') }}
												</x-block.context-menu-item>
											@endif

											<!-- Leave Team -->
											@if (Auth::user()->id === $user->id && $team->user_id !== $user->id)
												<x-block.context-menu-item
														@click="
															confirmingLeavingTeam = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $user->id }}').hide();
														"
														class="text-rose-500"
												>
													{{ __('Leave') }}
												</x-block.context-menu-item>
											@endif
										</x-block.context-menu>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="5" class="px-5 py-4 text-center">
										<span class="text-gray-400 dark:text-gray-500">
											{{ __('No users found') }}
										</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $users->links() }}
						</nav>

						<!-- Modals -->

						<!-- User Tags Modal -->
						@if (Gate::check('manageUserTags', $team))
							<x-modals.users.manage-user-tags :team="$team" />
						@endif

						<!-- Remove Team Member Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="confirmingTeamMemberRemoval" x-cloak>
							<x-slot name="title">
								{{ __('Remove Team Member') }}
							</x-slot>

							<x-slot name="content">
								{{ __('Are you sure you would like to remove this person from the team?') }}
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="confirmingTeamMemberRemoval = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-danger-button class="ms-3" wire:click="removeTeamMember" wire:loading.attr="disabled">
									{{ __('Remove') }}
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

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
									{{ __('Leave') }}
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Add Team Member Modal -->
	<x-dialog-modal wire:model.live="addTeamMemberModal">
		<x-slot name="title">
			{{ __('Add Team Member') }}
		</x-slot>

		<x-slot name="content">
			<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

				<!-- Member Email -->
				<div class="col-span-6 sm:col-span-4">
					<x-form.label.label-required for="email" value="{{ __('Email') }}" />
					<x-form.input.input-icon icon="fa-solid fa-envelope" id="email" type="email" wire:model="addTeamMemberForm.email" placeholder="name@example.org" />
					<x-input-error for="email" />
					<p id="helper-text-explanation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
						{{ __('Please provide the email address of the person you would like to add to this team.') }}
					</p>
				</div>

				<!-- Role -->
				@if (count($this->roles) > 0)
					<div class="col-span-6 lg:col-span-4 mt-8">
						<x-form.label.label-required for="role" value="{{ __('Role') }}" />

						<div class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer">
							@foreach ($this->roles as $index => $role)
								<button type="button" class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 {{ $index > 0 ? 'border-t border-gray-200 focus:border-none rounded-t-none' : '' }} {{ ! $loop->last ? 'rounded-b-none' : '' }}"
										wire:click="$set('addTeamMemberForm.role', '{{ $role->key }}')">
									<div class="{{ isset($addTeamMemberForm['role']) && $addTeamMemberForm['role'] !== $role->key ? 'opacity-50' : '' }}">
										<!-- Role Name -->
										<div class="flex items-center">
											<div class="text-sm text-gray-600 dark:text-gray-200 {{ $addTeamMemberForm['role'] == $role->key ? 'font-semibold' : '' }}">
												{{ $role->name }}
											</div>

											@if ($addTeamMemberForm['role'] == $role->key)
												<svg class="ms-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
												</svg>
											@endif
										</div>

										<!-- Role Description -->
										<div class="mt-2 text-xs text-gray-600 text-start dark:text-gray-300">
											{{ $role->description }}
										</div>
									</div>
								</button>
							@endforeach
						</div>

						<x-input-error for="role" />
					</div>
				@endif

				<!-- Pending Invites -->
				@if (count($team->teamInvitations) > 0)
					<div class="col-span-6 sm:col-span-4 mt-8">
						<x-label for="email" value="{{ __('Pending Team Invitations') }}" />
						<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
							{{ __('These people have been invited to your team and have been sent an invitation email. They may join the team by accepting the email invitation.') }}
						</p>
						<div class="mt-4">
							@foreach ($team->teamInvitations as $invitation)
								<span class="inline-flex items-center px-2 py-1 me-2 mb-2 text-sm font-medium text-blue-800 bg-blue-100 rounded dark:bg-blue-900 dark:text-blue-300">
									{{ $invitation->email }}
									@if (Gate::check('removeTeamMember', $team))
										<button wire:click="cancelTeamInvitation({{ $invitation->id }})" wire:loading.attr="disabled" type="button" class="inline-flex items-center p-1 ms-2 text-sm text-blue-400 bg-transparent rounded-sm hover:bg-blue-200 hover:text-blue-900 dark:hover:bg-blue-800 dark:hover:text-blue-300" aria-label="Remove">
											<svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
												<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
											</svg>
											<span class="sr-only">Remove invitation</span>
										</button>
									@endif
								</span>
							@endforeach
						</div>
					</div>
				@endif

				<x-input-error for="team" />

			</div>
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('addTeamMemberModal')" wire:loading.attr="disabled">
				{{ __('Close') }}
			</x-secondary-button>

			<x-button class="ms-3" wire:click="addTeamMember" wire:loading.attr="disabled">
				{{ __('Add') }}
			</x-button>
		</x-slot>
	</x-dialog-modal>

	<!-- Role Management Modal -->
	<x-dialog-modal wire:model.live="currentlyManagingRole">
		<x-slot name="title">
			{{ __('Manage Role') }}
		</x-slot>

		<x-slot name="content">
			<div class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer">
				@foreach ($this->roles as $index => $role)
					<button type="button" class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 {{ $index > 0 ? 'border-t border-gray-200 focus:border-none rounded-t-none' : '' }} {{ ! $loop->last ? 'rounded-b-none' : '' }}"
							wire:click="$set('currentRole', '{{ $role->key }}')">
						<div class="{{ $currentRole !== $role->key ? 'opacity-50' : '' }}">
							<!-- Role Name -->
							<div class="flex items-center">
								<div class="text-sm text-gray-600 dark:text-slate-200 {{ $currentRole == $role->key ? 'font-semibold' : '' }}">
									{{ $role->name }}
								</div>

								@if ($currentRole == $role->key)
									<svg class="ms-2 h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
									</svg>
								@endif
							</div>

							<!-- Role Description -->
							<div class="mt-2 text-xs text-gray-600 dark:text-slate-300">
								{{ $role->description }}
							</div>
						</div>
					</button>
				@endforeach
			</div>
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('currentlyManagingRole')" wire:loading.attr="disabled">
				{{ __('Cancel') }}
			</x-secondary-button>

			<x-button class="ms-3" wire:click="updateRole" wire:loading.attr="disabled">
				{{ __('Save') }}
			</x-button>
		</x-slot>
	</x-dialog-modal>

	<!-- Send User Message Modal -->
	<x-dialog-modal wire:model.live="sendUserMessageModal">
		<x-slot name="title">
			{{ __('Send Message') }}
		</x-slot>

		<x-slot name="content">
			<x-forms.send-message :selectedUser="$selectedUser" :message="$message" />
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('sendUserMessageModal')" wire:loading.attr="disabled">
				{{ __('Close') }}
			</x-secondary-button>

			<x-button class="ms-3" wire:click="sendMessage" wire:loading.attr="disabled">
				{{ __('Send') }}
			</x-button>
		</x-slot>
	</x-dialog-modal>

	<!-- Send Team Message Modal -->
	<x-dialog-modal wire:model.live="sendTeamMessageModal">
		<x-slot name="title">
			{{ __('Send Message') }}
		</x-slot>

		<x-slot name="content">
			<x-forms.send-message :message="$message" :sendTeamMessageTo="$sendTeamMessageTo" :recipients="$this->recipients" />
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('sendTeamMessageModal')" wire:loading.attr="disabled">
				{{ __('Close') }}
			</x-secondary-button>

			<x-button class="ms-3" wire:click="sendMessage" wire:loading.attr="disabled">
				{{ __('Send') }}
			</x-button>
		</x-slot>
	</x-dialog-modal>

	<!-- Edit Team Modal -->
	<x-dialog-modal wire:model.live="editTeamModal">
		<x-slot name="title">
			{{ __('Edit Team') }}
		</x-slot>

		<x-slot name="content">
			<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

				<!-- Team Owner Information -->
				<div class="col-span-6 sm:col-span-4">
					<x-label value="{{ __('Team Owner') }}" />

					<div class="flex items-center mt-2" autofocus>
						<img class="w-12 h-12 rounded-full object-cover" src="{{ $team->owner->profile_photo_url }}" alt="{{ $team->owner->name }}">

						<div class="ms-4 leading-tight">
							<div class="text-gray-900 dark:text-slate-200">{{ $team->owner->name }}</div>
							<div class="text-gray-700 text-sm dark:text-slate-500">{{ $team->owner->email }}</div>
						</div>
					</div>
				</div>

				<!-- Team Name -->
				<div class="col-span-6 sm:col-span-4 mt-8">
					<x-form.label.label-required for="teamName" value="{{ __('Team Name') }}" />
					<x-input
						id="teamName"
						type="text"
						class="mt-1 block w-full"
						wire:model="teamName"
					/>
					<x-input-error for="teamName" />
				</div>

				@if (Gate::check('delete', $team) && !$team->personal_team)
					<livewire:teams.delete-team-form :team="$team" />
				@endif

			</div>
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('editTeamModal')" wire:loading.attr="disabled">
				{{ __('Close') }}
			</x-secondary-button>

			<x-button class="ms-3" wire:click="saveTeam" wire:loading.attr="disabled">
				{{ __('Save') }}
			</x-button>
		</x-slot>
	</x-dialog-modal>

	<!-- Api Modal -->
	<x-dialog-modal wire:model.live="apiTokenModal">
		<x-slot name="title">
			{{ __('API Token') }}
		</x-slot>

		<x-slot name="content">
			<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

				<p class="text-gray-500 dark:text-gray-400 mb-6">
					To authenticate with our API, you can use the following API token. If you need to generate a new token, you can do so by clicking the button below.
				</p>

				@if ($apiTokenPlain)
					<div class="flex items-center p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
						<svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
							<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
						</svg>
						<div>
							Make sure to copy your new API token now as you will not be able to see this again.
						</div>
					</div>
				@endif

				<!-- Api Token -->
				<div class="col-span-6 sm:col-span-4 mt-8">

					<label for="api-key" class="text-sm font-medium text-gray-900 dark:text-white mb-2 block">API token</label>
					<div class="relative mb-4">
						@if ($apiTokenPlain)
							<div id="api-key-wrapper">
								<input
										id="api-key"
										wire:model="apiTokenPlain"
										type="text"
										class="col-span-6 bg-gray-50 border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
										disabled
										readonly
								>
							</div>
						@else
							@if ($apiToken)
								<div class="flex items-center justify-between rounded-lg border border-dashed p-4 gap-2 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-500 mb-4">
									<div class="ms-3 text-sm">
										<span class="font-medium">Created: </span> {{ $apiToken->created_at->format('M d, Y') }}<br />
										<span class="font-medium">Last Used: </span> {{ $apiToken->last_used_at ? $apiToken->last_used_at->diffForHumans() : 'Never' }}
									</div>
									<x-danger-button @click.prevent="showDeleteApiToken = true" class="ms-3">
										{{ __('Delete') }}
									</x-danger-button>
								</div>
							@else
								<div class="p-4 mb-4 text-sm text-gray-500 dark:text-gray-400 rounded-lg bg-gray-50 dark:bg-gray-800" role="alert">
									{{ __('No API token has been generated for this team.') }}
								</div>
							@endif
						@endif
					</div>

				</div>

				<div class="mt-5">
					<x-button wire:click.prevent="generateNewApiToken" wire:loading.attr="disabled" autofocus>
						{{ __('Generate New Token') }}
					</x-button>
				</div>

			</div>
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('apiTokenModal')" wire:loading.attr="disabled">
				{{ __('Close') }}
			</x-secondary-button>
		</x-slot>
	</x-dialog-modal>

	<!-- Delete Api Token Modal -->
	<x-modals.confirmation-modal-alpine var="showDeleteApiToken" maxWidth="sm" x-cloak>
		<x-slot name="title">
			{{ __('Delete API Token') }}
		</x-slot>

		<x-slot name="content">
			Are you sure you would like to delete this API token?
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button @click.prevent="showDeleteApiToken = false">
				{{ __('Cancel') }}
			</x-secondary-button>

			<x-button wire:click.prevent="deleteApiToken(); showDeleteApiToken = false" wire:loading.attr="disabled" class="bg-red-500 hover:bg-red-600 ms-3">
				{{ __('Delete') }}
			</x-button>
		</x-slot>
	</x-modals.confirmation-modal-alpine>
</div>
