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
						<div class="flex flex-wrap gap-3">
							<h2 class="font-bold text-slate-800 dark:text-slate-100">
								Users
							</h2>
							<!-- Total Users -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $users->total() }} {{ Str::plural('user', $users->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
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
								<input wire:model.live.debounce.500ms="query" type="text" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search for users" />
							</div>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
						</div>


					</div>

				</div>

				<div class="p-3">
					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto" x-data="{
							verifyConfirmation: @entangle('verifyConfirmation'),
							verifyUserId: @entangle('verifyUserId'),
							deleteConfirmation: @entangle('deleteConfirmation'),
							deleteUserId: @entangle('deleteUserId'),
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
									Verified
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
										<span class="bg-gray-100 text-gray-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded me-2 dark:bg-gray-700 dark:text-gray-400 border border-gray-500 ">
											<svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
												<path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
											</svg>
											{{ $user->created_at->format('M d, Y H:i') }}
										</span>
									</td>
									<td class="px-5 py-4">
										@if ($user->email_verified_at)
											<span class="bg-blue-100 text-blue-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
												<svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
													<path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
												</svg>
												{{ $user->email_verified_at->format('M d, Y H:i') }}
											</span>
										@else
											<button
													class="bg-red-100 text-red-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400"
													@click="
														verifyUserId = {{ $user->id }};
														verifyConfirmation = true;
													"
											>
												<svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
													<path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
												</svg>
												{{ __('Not verified') }}
											</button>
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

					</div>

				</div>
			</div>
		</div>
	</div>

</div>
