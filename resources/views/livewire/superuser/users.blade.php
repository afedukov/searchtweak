<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Users') }}
		</h2>
	</x-slot>

	<div>
		<!-- Users -->
		<div class="px-4 sm:px-5 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap items-center gap-3">
							<h2 class="font-bold text-slate-800 dark:text-slate-100">
								Users
							</h2>
							<!-- Total Users -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $users->total() }} {{ Str::plural('user', $users->total()) }}
							</span>
							@if ($onlineCount > 0)
								<span class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
									<span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
									{{ $onlineCount }} online
								</span>
							@endif
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<x-button wire:click="createUser" class="flex items-center">
								<svg class="w-4 h-4 fill-current opacity-50 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 18">
									<path d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-2V5a1 1 0 0 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 0 0 2 0V9h2a1 1 0 1 0 0-2Z"/>
								</svg>
								<span class="ml-2">{{ __('Create User') }}</span>
							</x-button>
						</div>

					</div>

				</header>

				<!-- Second Header: Search + Filters -->
				<div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap items-center gap-4">

						<!-- Search Box -->
						<livewire:components.search-box wire:model.live="query" placeholder="Search for users" key="users-search-box" />

						<!-- Role Filter -->
						<div class="inline-flex rounded-md items-center" role="group">
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterRole" wire:loading.attr="disabled" name="users-filter-role" id="users-filter-role-all" value="" class="hidden peer" />
								<label for="users-filter-role-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									{{ __('All') }}
								</label>
							</div>
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterRole" wire:loading.attr="disabled" name="users-filter-role" id="users-filter-role-super-admin" value="super_admin" class="hidden peer" />
								<label for="users-filter-role-super-admin" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									{{ __('Super Admin') }}
								</label>
							</div>
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterRole" wire:loading.attr="disabled" name="users-filter-role" id="users-filter-role-regular" value="regular" class="hidden peer" />
								<label for="users-filter-role-regular" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									{{ __('Regular') }}
								</label>
							</div>
						</div>

						<!-- Verified Filter -->
						<div class="inline-flex rounded-md items-center" role="group">
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterVerified" wire:loading.attr="disabled" name="users-filter-verified" id="users-filter-verified-all" value="" class="hidden peer" />
								<label for="users-filter-verified-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									{{ __('All') }}
								</label>
							</div>
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterVerified" wire:loading.attr="disabled" name="users-filter-verified" id="users-filter-verified-yes" value="verified" class="hidden peer" />
								<label for="users-filter-verified-yes" class="inline-flex items-center gap-1 px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									<svg class="w-3.5 h-3.5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
									{{ __('Verified') }}
								</label>
							</div>
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterVerified" wire:loading.attr="disabled" name="users-filter-verified" id="users-filter-verified-no" value="not_verified" class="hidden peer" />
								<label for="users-filter-verified-no" class="inline-flex items-center gap-1 px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									<svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
									{{ __('Not Verified') }}
								</label>
							</div>
						</div>

						<!-- Online Filter -->
						<div class="inline-flex rounded-md items-center" role="group">
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterOnline" wire:loading.attr="disabled" name="users-filter-online" id="users-filter-online-all" value="" class="hidden peer" />
								<label for="users-filter-online-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									{{ __('All') }}
								</label>
							</div>
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterOnline" wire:loading.attr="disabled" name="users-filter-online" id="users-filter-online-yes" value="online" class="hidden peer" />
								<label for="users-filter-online-yes" class="inline-flex items-center gap-1.5 px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									<span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
									{{ __('Online') }}
								</label>
							</div>
							<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
								<input type="radio" wire:model.live="filterOnline" wire:loading.attr="disabled" name="users-filter-online" id="users-filter-online-no" value="offline" class="hidden peer" />
								<label for="users-filter-online-no" class="inline-flex items-center gap-1.5 px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
									<span class="inline-block w-2 h-2 bg-gray-400 rounded-full"></span>
									{{ __('Offline') }}
								</label>
							</div>
						</div>

					</div>

				</div>

				<div class="p-3">
					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto" x-data="{
							verifyConfirmation: $wire.entangle('verifyConfirmation'),
							verifyUserId: $wire.entangle('verifyUserId'),
							deleteConfirmation: $wire.entangle('deleteConfirmation'),
							deleteUserId: $wire.entangle('deleteUserId'),
							superAdminConfirmation: $wire.entangle('superAdminConfirmation'),
							superAdminUserId: $wire.entangle('superAdminUserId'),
						}"
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
									Joined
								</th>
								<th scope="col" class="px-5 py-3">
									Last Active
								</th>
								<th scope="col" class="px-5 py-3 w-36 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($users as $user)
									<tr wire:key="user-{{ $user->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
										<th scope="row" class="px-5 py-4 font-medium text-gray-900 dark:text-white align-baseline">
											<div class="inline-flex items-center gap-2 max-w-[280px]">
												<x-block.user-name :user="$user" />
												@if ($user->super_admin)
													<span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-indigo-900 dark:text-indigo-300 whitespace-nowrap">Super Admin</span>
												@endif
											</div>
										</th>
									<td class="px-5 py-4 align-baseline">
										<span class="text-gray-500 dark:text-gray-400">
											{{ $user->email }}
										</span>
									</td>
									<td class="px-5 py-4 align-baseline">
										<div class="inline-flex items-center gap-2">
											<span class="text-sm text-gray-500 dark:text-gray-400">
												{{ $user->created_at->format('M d, Y') }}
											</span>
											@if ($user->email_verified_at)
												<span data-popover-target="user-verified-{{ $user->id }}" class="inline-flex items-center justify-center text-green-400 cursor-pointer">
													<svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
														<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
													</svg>
												</span>
												<x-tooltip id="user-verified-{{ $user->id }}" with-arrow>
													<span class="whitespace-nowrap">
														<ul>
															<li>
																<span class="font-bold">Joined</span>
																<svg class="w-3 h-3 text-gray-400 inline" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
																	<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
																</svg>
																<span class="font-medium">{{ $user->created_at->format('M d, Y H:i') }}</span>
															</li>
															<li>
																<span class="font-bold">Verified</span>
																<svg class="w-3 h-3 text-green-400 inline" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
																	<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
																</svg>
																<span class="font-medium">{{ $user->email_verified_at->format('M d, Y H:i') }}</span>
															</li>
														</ul>
													</span>
												</x-tooltip>
											@else
												<button
													data-popover-target="user-not-verified-{{ $user->id }}"
													class="inline-flex items-center justify-center text-red-500 hover:text-red-700"
													@click="
														verifyUserId = {{ $user->id }};
														verifyConfirmation = true;
													"
												>
													<svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
														<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
													</svg>
												</button>
												<x-tooltip id="user-not-verified-{{ $user->id }}" with-arrow>
													<span class="whitespace-nowrap">
														<ul>
															<li>
																<span class="font-bold">Joined</span>
																<svg class="w-3 h-3 text-gray-400 inline" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
																	<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
																</svg>
																<span class="font-medium">{{ $user->created_at->format('M d, Y H:i') }}</span>
															</li>
															<li>
																<span class="font-bold text-red-500">Not verified</span>
																<span class="font-medium text-red-400">- click to verify</span>
															</li>
														</ul>
													</span>
												</x-tooltip>
											@endif
										</div>
									</td>
									<td class="px-5 py-4 align-baseline">
										@if ($user->wasRecentlyActive())
											<span class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
												<span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
												Online
											</span>
										@elseif ($user->last_active_at)
											<span class="text-sm text-gray-500 dark:text-gray-400">
												{{ $user->last_active_at->diffForHumans() }}
											</span>
										@else
											<span class="text-sm text-gray-400 dark:text-gray-500">
												Never
											</span>
										@endif
									</td>
									<td class="px-5 py-4 text-right align-baseline">
										<x-block.context-menu id="context-{{ $user->id }}">
											@if ($user->id !== Auth::id())
												<x-block.context-menu-item :href="route('superuser.impersonate', $user)">
													Impersonate
												</x-block.context-menu-item>
												<x-block.context-menu-item
														@click="
															superAdminUserId = {{ $user->id }};
															superAdminConfirmation = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $user->id }}').hide();
														"
												>
													{{ $user->super_admin ? __('Revoke Super Admin') : __('Grant Super Admin') }}
												</x-block.context-menu-item>
												<x-block.context-menu-item
														@click="
															deleteUserId = {{ $user->id }};
															deleteConfirmation = true;
															FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $user->id }}').hide();
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

						<!-- Verify Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="verifyConfirmation" x-cloak>
							<x-slot name="title">
								Verify Email
							</x-slot>

							<x-slot name="content">
								Are you sure you want to verify this user's email?
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="verifyConfirmation = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-button class="ms-3" wire:click="verifyEmail" wire:loading.attr="disabled">
									Verify
								</x-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

						<!-- Delete Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="deleteConfirmation" x-cloak>
							<x-slot name="title">
								Delete User
							</x-slot>

							<x-slot name="content">
								Are you sure you want to delete this user?
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="deleteConfirmation = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-danger-button class="ms-3" wire:click="deleteUser" wire:loading.attr="disabled">
									Delete
								</x-danger-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

						<!-- Super Admin Confirmation Modal -->
						<x-modals.confirmation-modal-alpine var="superAdminConfirmation" x-cloak>
							<x-slot name="title">
								Toggle Super Admin
							</x-slot>

							<x-slot name="content">
								Are you sure you want to change this user's super admin status?
							</x-slot>

							<x-slot name="footer">
								<x-secondary-button @click.prevent="superAdminConfirmation = false" wire:loading.attr="disabled">
									{{ __('Cancel') }}
								</x-secondary-button>

								<x-button class="ms-3" wire:click="toggleSuperAdmin" wire:loading.attr="disabled">
									Confirm
								</x-button>
							</x-slot>
						</x-modals.confirmation-modal-alpine>

					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Create User Modal -->
	<x-modals.user-create />

</div>
